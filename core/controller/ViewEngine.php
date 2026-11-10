<?php
/**
 * ViewEngine - Twig 3 Wrapper
 * Proyecto: AtomCredit 2 (Legobox 5)
 */
class ViewEngine {
    private static ?\Twig\Environment $twig = null;

    public static function init(): \Twig\Environment {
        if (self::$twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../public');
            self::$twig = new \Twig\Environment($loader, [
                'cache' => false,
                'debug' => true,
            ]);

            $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            self::$twig->addGlobal('base_url', $baseUrl);

            self::$twig->addGlobal('csrf_token', Session::csrfToken());
            self::$twig->addFunction(new \Twig\TwigFunction('csrf_token', function() {
                return Session::csrfToken();
            }));
            self::$twig->addFunction(new \Twig\TwigFunction('csrf_field', function() {
                return '<input type="hidden" name="csrf_token" value="' . Session::csrfToken() . '">';
            }, ['is_safe' => ['html']]));
        }
        return self::$twig;
    }

    public static function render(string $template, array $data = []): void {
        $twig = self::init();
        $curr_user = isset($_SESSION['user_id']) ? UserData::getById($_SESSION['user_id']) : null;
        $twig->addGlobal('curr_user', $curr_user);

        // Global currency and settings
        $currency = 'Q';
        if (class_exists('SettingData')) {
            $currency = SettingData::currency();
        }
        $twig->addGlobal('currency', $currency);

        foreach (['success', 'updated', 'deleted', 'error'] as $key) {
            $twig->addGlobal('flash_' . $key, $_SESSION[$key] ?? null);
            unset($_SESSION[$key]);
        }

        echo $twig->render($template, $data);
    }
}
?>
