<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Models\Category;
use App\Models\Skill;
use App\Services\AuditService;
use App\Services\ProjectService;

/**
 * Category and skill management.
 */
final class CatalogController extends AdminController
{
    public function categories(Request $request): string
    {
        return $this->adminView('admin/catalog/categories', [
            'title'          => 'Categories',
            'categories'     => Category::allOrdered(),
            'adminNavActive' => 'categories',
        ]);
    }

    public function saveCategory(Request $request): mixed
    {
        $id = $request->int('category_id');
        $name = $request->string('name');

        if (mb_strlen($name) < 2) {
            $this->err('Please provide a category name.', 'admin.categories');
        }

        $slug = ProjectService::slugify($request->string('slug') !== '' ? $request->string('slug') : $name);
        $existing = Category::findBy(['slug' => $slug]);

        if ($existing !== null && (int) $existing['id'] !== $id) {
            $this->err('A category with that slug already exists.', 'admin.categories');
        }

        $payload = [
            'name'        => mb_substr($name, 0, 120),
            'slug'        => mb_substr($slug, 0, 140),
            'description' => $request->string('description') !== '' ? mb_substr($request->string('description'), 0, 500) : null,
            'icon'        => $request->string('icon', 'bi-briefcase'),
            'is_active'   => $request->bool('is_active') ? 1 : 0,
            'sort_order'  => $request->int('sort_order', 0),
        ];

        if ($id > 0) {
            Category::update($id, $payload);
            AuditService::record('admin.category_updated', 'Category updated', 'category', $id);
            $this->ok('The category has been updated.', 'admin.categories');
        }

        $newId = Category::create($payload);
        AuditService::record('admin.category_created', 'Category created', 'category', $newId);

        $this->ok('The category has been created.', 'admin.categories');
    }

    public function skills(Request $request): string
    {
        return $this->adminView('admin/catalog/skills', [
            'title'          => 'Skills',
            'skills'         => Skill::allOrdered(),
            'categories'     => Category::activeList(),
            'adminNavActive' => 'skills',
        ]);
    }

    public function saveSkill(Request $request): mixed
    {
        $id = $request->int('skill_id');
        $name = $request->string('name');

        if (mb_strlen($name) < 2) {
            $this->err('Please provide a skill name.', 'admin.skills');
        }

        $slug = ProjectService::slugify($name);
        $existing = Skill::findBy(['slug' => $slug]);

        if ($existing !== null && (int) $existing['id'] !== $id) {
            $this->err('A skill with that name already exists.', 'admin.skills');
        }

        $categoryId = $request->int('category_id');

        $payload = [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'name'        => mb_substr($name, 0, 120),
            'slug'        => mb_substr($slug, 0, 140),
            'is_active'   => $request->bool('is_active') ? 1 : 0,
        ];

        if ($id > 0) {
            Skill::update($id, $payload);
            AuditService::record('admin.skill_updated', 'Skill updated', 'skill', $id);
            $this->ok('The skill has been updated.', 'admin.skills');
        }

        $newId = Skill::create($payload);
        AuditService::record('admin.skill_created', 'Skill created', 'skill', $newId);

        $this->ok('The skill has been created.', 'admin.skills');
    }

    public function toggleCategory(Request $request): never
    {
        $id = (int) ($request->route('id') ?? 0);
        $category = Category::find($id);

        if ($category === null) {
            $this->err('That category could not be found.', 'admin.categories');
        }

        Category::update($id, ['is_active' => (int) $category['is_active'] === 1 ? 0 : 1]);
        AuditService::record('admin.category_toggled', 'Category availability changed', 'category', $id);

        $this->ok('Category availability updated.', 'admin.categories');
    }

    public function toggleSkill(Request $request): never
    {
        $id = (int) ($request->route('id') ?? 0);
        $skill = Skill::find($id);

        if ($skill === null) {
            $this->err('That skill could not be found.', 'admin.skills');
        }

        Skill::update($id, ['is_active' => (int) $skill['is_active'] === 1 ? 0 : 1]);
        AuditService::record('admin.skill_toggled', 'Skill availability changed', 'skill', $id);

        $this->ok('Skill availability updated.', 'admin.skills');
    }
}
