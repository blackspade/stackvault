<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DashboardModel;

class DashboardController extends Controller
{
    /** Root / redirect — send authenticated users to /dashboard, others to /login. */
    public function redirectHome(): void
    {
        $this->redirect(is_auth() ? '/dashboard' : '/login');
    }

    /** GET /dashboard */
    public function index(): void
    {
        $stats              = DashboardModel::getStats();
        $expiringDomains    = DashboardModel::getExpiringDomains(30);
        $expiringSsl        = DashboardModel::getExpiringSsl(30);
        $recentCreds        = DashboardModel::getRecentCredentials(5);
        $osBreakdown        = DashboardModel::getServerOsBreakdown();
        $failedCount24h     = DashboardModel::getFailedLoginCount24h();
        $failedLogins       = DashboardModel::getRecentFailedLogins(8);
        $recentActivity     = DashboardModel::getRecentActivity(10);
        $upcomingReminders  = DashboardModel::getUpcomingReminders(30);

        $this->view('dashboard/index', [
            'title'              => 'Dashboard',
            'stats'              => $stats,
            'expiringDomains'    => $expiringDomains,
            'expiringSsl'        => $expiringSsl,
            'recentCreds'        => $recentCreds,
            'osBreakdown'        => $osBreakdown,
            'failedCount24h'     => $failedCount24h,
            'failedLogins'       => $failedLogins,
            'recentActivity'     => $recentActivity,
            'upcomingReminders'  => $upcomingReminders,
        ]);
    }
}
