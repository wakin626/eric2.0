<?php
namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationController {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public function getUnread() {
        header('Content-Type: application/json');
        try {
            $userId = $_SESSION['user_id'];
            $department = $_SESSION['department'] ?? '';
            $notifications = NotificationModel::getUnreadForUser($userId, $department, 20);
            $count = NotificationModel::getUnreadCount($userId, $department);
            echo json_encode(['count' => $count, 'notifications' => $notifications]);
        } catch (\Exception $e) {
            error_log('getUnread error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load notifications', 'count' => 0, 'notifications' => []]);
        }
        exit;
    }

    public function markRead() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        try {
            $notificationId = $_POST['notification_id'] ?? null;
            if (!$notificationId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing notification_id']);
                exit;
            }
            NotificationModel::markAsRead($notificationId, $_SESSION['user_id']);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('markRead error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to mark as read']);
        }
        exit;
    }

    public function markAllRead() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        try {
            NotificationModel::markAllAsRead($_SESSION['user_id'], $_SESSION['department'] ?? '');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('markAllRead error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to mark all as read']);
        }
        exit;
    }
}
