<?php
namespace App\Models;

use App\Core\BaseModel;

class UserModel extends BaseModel {
    protected $table = 'users';

    public function getByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (username, email, password, full_name, department) 
                VALUES (:username, :email, :password, :full_name, :department)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'full_name' => $data['full_name'],
            'department' => $data['department']
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                full_name = :full_name, 
                email = :email, 
                department = :department,
                status = :status
                WHERE user_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'department' => $data['department'],
            'status' => $data['status'] ?? 1
        ]);
    }

    public function changePassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET password = :password WHERE user_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'password' => $newPassword
        ]);
    }

    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET `remove` = 1 WHERE user_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function verifyLogin($username, $password) {
        $user = $this->getByUsername($username);
        if (!$user) {
            return false;
        }

        $storedPassword = $user['password'];
        if (password_verify($password, $storedPassword) || $password === $storedPassword) {
            return $user;
        }

        return false;
    }

    public function getAll($activeOnly = false) {
        $sql = "SELECT * FROM {$this->table} WHERE `remove` = 0";
        if ($activeOnly) {
            $sql .= " AND status = 1";
        }
        $sql .= " ORDER BY date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}