<?php

use PHPUnit\Framework\TestCase;
use RMM\FrontendDashboard\AuthController;

class AuthControllerTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['rmm_test_users'] = [];
        $GLOBALS['rmm_test_current_user_id'] = 0;
        $GLOBALS['rmm_test_terms'] = [];
    }

    private function addUser($id, array $roles = [], array $caps = []) {
        $user = new RMM_Test_User($id, $roles, $caps);
        $GLOBALS['rmm_test_users'][$id] = $user;
        return $user;
    }

    public function test_kitchen_staff_can_update_kitchen_orders_but_not_manage_menu() {
        $kitchen = $this->addUser(10, ['kitchen_staff']);

        $this->assertTrue(AuthController::is_authorized($kitchen));
        $this->assertTrue(AuthController::can_view_orders($kitchen));
        $this->assertTrue(AuthController::can_update_order_status($kitchen));
        $this->assertFalse(AuthController::can_manage_menu($kitchen));
        $this->assertFalse(AuthController::can_manage_branches($kitchen));
        $this->assertFalse(AuthController::can_view_catalog($kitchen));
    }

    public function test_restaurant_manager_can_manage_menu_but_not_global_branch_records() {
        $manager = $this->addUser(11, ['restaurant_manager'], ['manage_restaurant_menu']);

        $this->assertTrue(AuthController::can_manage_menu($manager));
        $this->assertTrue(AuthController::can_manage_inventory($manager));
        $this->assertTrue(AuthController::can_update_order_status($manager));
        $this->assertFalse(AuthController::can_manage_branches($manager));
        $this->assertFalse(AuthController::is_global_manager(11));
    }

    public function test_branch_scoped_roles_remain_restricted_even_with_legacy_woocommerce_capability() {
        $manager = $this->addUser(12, ['restaurant_manager'], ['manage_woocommerce']);

        $this->assertFalse(AuthController::is_global_manager(12));
        $this->assertFalse(AuthController::user_can_access_branch(99, 12));
    }

    public function test_shop_manager_is_global_and_can_access_all_branches() {
        $this->addUser(13, ['shop_manager'], ['manage_woocommerce']);
        $GLOBALS['rmm_test_terms'] = [4, 9];

        $this->assertTrue(AuthController::is_global_manager(13));
        $this->assertSame([4, 9], AuthController::get_user_branches(13));
        $this->assertTrue(AuthController::user_can_access_branch(9, 13));
    }

    public function test_administrator_capability_is_global_even_without_role_metadata() {
        $this->addUser(14, [], ['manage_options']);

        $this->assertTrue(AuthController::is_global_manager(14));
        $this->assertTrue(AuthController::user_can_access_branch(123, 14));
    }

    public function test_unknown_user_cannot_access_branch() {
        $this->assertFalse(AuthController::user_can_access_branch(4, 404));
        $this->assertFalse(AuthController::is_global_manager(404));
    }
}
