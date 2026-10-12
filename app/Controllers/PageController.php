<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\SubscriptionPlan;

/**
 * Static marketing, help and legal pages.
 */
final class PageController extends Controller
{
    public function howItWorks(Request $request): string
    {
        return $this->view('pages/how-it-works', [
            'title' => 'How CircleWork Africa works',
        ], 'layouts/public');
    }

    public function pricing(Request $request): string
    {
        return $this->view('pages/pricing', [
            'title' => 'Pricing and plans',
            'clientPlans' => SubscriptionPlan::activeFor('client'),
            'freelancerPlans' => SubscriptionPlan::activeFor('freelancer'),
            'featuredPrice' => \App\Services\FeaturedService::price(),
            'featuredDays' => \App\Services\FeaturedService::durationDays(),
        ], 'layouts/public');
    }

    public function about(Request $request): string
    {
        return $this->view('pages/about', [
            'title' => 'About CircleWork Africa',
        ], 'layouts/public');
    }

    public function contact(Request $request): string
    {
        return $this->view('pages/contact', [
            'title' => 'Contact and help',
        ], 'layouts/public');
    }

    public function faq(Request $request): string
    {
        return $this->view('pages/faq', [
            'title' => 'Frequently asked questions',
        ], 'layouts/public');
    }

    public function terms(Request $request): string
    {
        return $this->view('pages/terms', ['title' => 'Terms of Service'], 'layouts/public');
    }

    public function privacy(Request $request): string
    {
        return $this->view('pages/privacy', ['title' => 'Privacy Policy'], 'layouts/public');
    }

    public function guidelines(Request $request): string
    {
        return $this->view('pages/guidelines', ['title' => 'Community Guidelines'], 'layouts/public');
    }

    public function disputePolicy(Request $request): string
    {
        return $this->view('pages/dispute-policy', [
            'title' => 'Dispute and Cancellation Policy',
        ], 'layouts/public');
    }
}
