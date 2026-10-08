<?php
/** Default-branch double for the existing standalone suites; real isolation is tested in wp-branches-*.php. */
namespace FandooghRest\Branches;
final class Branches
{
    public const META = '_fandoogh_branch_id';
    public const USER_META = '_fandoogh_branch_ids';
    public const GLOBAL_SETTINGS = ['menu_slug', 'panel_slug', 'currency_code'];
    private static int $id=1;
    public static function current(): int { return self::$id; }
    public static function defaultId(): int { return 1; }
    public static function setCurrent(int $id): void { self::$id=$id; }
    public static function runFor(int $id, callable $call): mixed { $old=self::$id; self::$id=$id; try { return $call(); } finally { self::$id=$old; } }
    public static function get(int $id): ?array { return $id===1?['id'=>1,'name'=>'Test cafe','slug'=>'default','enabled'=>true,'settings'=>[]]:null; }
    public static function all(): array { return [self::get(1)]; }
    public static function productBranch($product): int { return 1; }
    public static function orderBranch($order): int { return 1; }
    public static function categoryBranch(int $id): int { return 1; }
    public static function ownsCategory(int $id): bool { return true; }
    public static function ownsProduct($product): bool { return true; }
    public static function ownsMedia(int $id): bool { return true; }
    public static function canAccess(int $id, ?int $user=null): bool { return $id===1; }
    public static function allowedIds(?int $user=null): array { return [1]; }
    public static function isCentral(?int $user=null): bool { return true; }
    public static function menuUrl(?int $id=null): string { return 'https://cafe.test/menu/'; }
}
