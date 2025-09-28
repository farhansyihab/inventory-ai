<?php
// src/Service/Metrics/UserMetrics.php

namespace App\Service\Metrics;

use App\Service\UserService;
use App\Exception\DashboardException;
use Psr\Log\LoggerInterface;
use DateTime;

class UserMetrics
{
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(UserService $userService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    public function getUserMetrics(): array
    {
        try {
            $this->logger->info('Collecting user metrics');

            $thirtyDaysAgo = new DateTime('-30 days');
            
            // Get basic user counts
            $totalUsers = $this->userService->count();
            $activeUsers = $this->userService->count([
                'last_login' => ['$gte' => $thirtyDaysAgo]
            ]);

            // Get role distribution
            $roleDistribution = $this->getRoleDistribution();
            
            // Get recent activity
            $recentActivity = $this->getRecentActivity();

            $metrics = [
                'demographics' => [
                    'totalUsers' => $totalUsers,
                    'activeUsers' => $activeUsers,
                    'inactiveUsers' => $totalUsers - $activeUsers
                ],
                'roleDistribution' => $roleDistribution,
                'activity' => [
                    'loginsToday' => $this->getTodayLoginCount(),
                    'activeNow' => $this->getCurrentlyActiveUsers(),
                    'averageSessionTime' => $this->getAverageSessionTime()
                ],
                'recentActivity' => $recentActivity
            ];

            $this->logger->info('User metrics collected successfully', [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers
            ]);

            return $metrics;

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect user metrics', [
                'error' => $e->getMessage()
            ]);

            throw DashboardException::serviceUnavailable('UserService', $e);
        }
    }

    private function getRoleDistribution(): array
    {
        $distribution = [];
        $roles = ['admin', 'manager', 'staff'];

        foreach ($roles as $role) {
            // This would use UserService::count(['role' => $role]) in real implementation
            $distribution[$role] = $this->userService->countByRole($role);
        }

        return $distribution;
    }

    private function getTodayLoginCount(): int
    {
        $today = new DateTime('today');
        return $this->userService->count([
            'last_login' => ['$gte' => $today]
        ]);
    }

    private function getCurrentlyActiveUsers(): int
    {
        $fifteenMinutesAgo = new DateTime('-15 minutes');
        return $this->userService->count([
            'last_activity' => ['$gte' => $fifteenMinutesAgo]
        ]);
    }

    private function getAverageSessionTime(): string
    {
        // This would typically come from session analytics
        // Returning a fixed value for now
        return '25m';
    }

    private function getRecentActivity(int $limit = 10): array
    {
        // This would typically come from UserService::getRecentActivity()
        return [
            [
                'userId' => 'user_001',
                'username' => 'john.doe',
                'action' => 'login',
                'timestamp' => (new DateTime())->format(DateTime::ATOM)
            ]
        ];
    }

    public function getUserAlerts(): array
    {
        $metrics = $this->getUserMetrics();
        $alerts = [];

        $inactiveRatio = $metrics['demographics']['inactiveUsers'] / max(1, $metrics['demographics']['totalUsers']) * 100;

        if ($inactiveRatio > 50) {
            $alerts[] = [
                'type' => 'users',
                'level' => 'warning',
                'title' => 'High Inactivity Rate',
                'message' => sprintf('%.1f%% of users are inactive', $inactiveRatio),
                'actionUrl' => '/users?filter=inactive'
            ];
        }

        if ($metrics['demographics']['totalUsers'] === 0) {
            $alerts[] = [
                'type' => 'users',
                'level' => 'warning',
                'title' => 'No Users Found',
                'message' => 'The system has no registered users',
                'actionUrl' => '/users/create'
            ];
        }

        return $alerts;
    }
}