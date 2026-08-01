<?php
namespace App\Models;

use App\Core\BaseModel;
use PDO;

class NotificationModel extends BaseModel {
    protected static $table = 'notifications';

    public static function create($title, $message, $type, $targetDepartment, $targetUrl = null, $createdBy = null) {
        $conn = self::getConnection();
        $sql = "INSERT INTO notifications (title, message, type, target_department, target_url, created_by)
                VALUES (:title, :message, :type, :target_department, :target_url, :created_by)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'target_department' => $targetDepartment,
            'target_url' => $targetUrl,
            'created_by' => $createdBy,
        ]);
        return $conn->lastInsertId();
    }

    public static function getUnreadForUser($userId, $department, $limit = 20) {
        $conn = self::getConnection();
        $sql = "SELECT n.*, u.full_name AS created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.user_id
                WHERE n.target_department = :department
                  AND n.notification_id NOT IN (
                      SELECT nr.notification_id FROM notification_reads nr WHERE nr.user_id = :user_id
                  )
                ORDER BY n.date_created DESC
                LIMIT :limit";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':department', $department, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getUnreadCount($userId, $department) {
        $conn = self::getConnection();
        $sql = "SELECT COUNT(*)
                FROM notifications n
                WHERE n.target_department = :department
                  AND n.notification_id NOT IN (
                      SELECT nr.notification_id FROM notification_reads nr WHERE nr.user_id = :user_id
                  )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'department' => $department,
            'user_id' => $userId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAsRead($notificationId, $userId) {
        $conn = self::getConnection();
        $sql = "INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (:notification_id, :user_id)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    public static function markAllAsRead($userId, $department) {
        $conn = self::getConnection();
        $sql = "INSERT IGNORE INTO notification_reads (notification_id, user_id)
                SELECT n.notification_id, :user_id
                FROM notifications n
                WHERE n.target_department = :department
                  AND n.notification_id NOT IN (
                      SELECT nr.notification_id FROM notification_reads nr WHERE nr.user_id = :user_id2
                  )";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'department' => $department,
            'user_id2' => $userId,
        ]);
    }

    public static function cleanup($daysOld = 30) {
        $conn = self::getConnection();
        $sql = "DELETE FROM notifications WHERE date_created < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['days' => $daysOld]);
        return $stmt->rowCount();
    }
}
