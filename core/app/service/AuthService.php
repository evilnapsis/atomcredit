<?php
namespace App\Service;

use UserData;

class AuthService {
    private static ?UserData $cachedUser = null;

    public static function check(): bool {
        return !empty($_SESSION['user_id']) && self::user() !== null;
    }

    public static function getRolId(): int {
        $u = self::user();
        return $u ? (int)$u->rol_id : (int)($_SESSION['user_rol_id'] ?? 0);
    }

    public static function isAgent(): bool {
        return false;
    }

    public static function isAdmin(): bool {
        return self::getRolId() === 1;
    }

    public static function user(): ?UserData {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        if (self::$cachedUser !== null && self::$cachedUser->id == $_SESSION['user_id']) {
            return self::$cachedUser;
        }
        $u = UserData::getById($_SESSION['user_id']);
        if (!$u) {
            unset($_SESSION['user_id']);
            self::$cachedUser = null;
            return null;
        }
        self::$cachedUser = $u;
        return $u;
    }

    public static function attempt(string $login, string $password): bool {
        $user = UserData::getByLogin($login);
        if (!$user || !$user->is_active) {
            return false;
        }

        $matches = false;
        if (password_verify($password, $user->password)) {
            $matches = true;
        } elseif ($user->password === sha1(md5($password))) {
            $matches = true;
        } elseif ($user->password === md5($password)) {
            $matches = true;
        }

        if ($matches) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_lastname'] = $user->lastname;
            $_SESSION['user_username'] = $user->username;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_rol_id'] = $user->rol_id;
            return true;
        }

        return false;
    }

    public static function logout(): void {
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_lastname'],
            $_SESSION['user_username'],
            $_SESSION['user_email'],
            $_SESSION['user_rol_id']
        );
    }
}
