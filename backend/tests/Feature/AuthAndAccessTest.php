<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/** JWT auth, admin panel access and the Roles & Permissions module. */
class AuthAndAccessTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_jwt_login_returns_token_roles_and_permissions_and_logout_revokes_it(): void
    {
        $owner = $this->owner();

        $response = $this->postJson('/api/v1/auth/login', ['email' => $owner->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.attributes.roles', ['shop_owner'])
            ->assertJsonStructure(['meta' => ['access_token', 'expires_in']]);

        $this->assertContains(PermissionName::OwnListingsManage->value, $response->json('data.attributes.permissions'));

        $headers = ['Authorization' => 'Bearer '.$response->json('meta.access_token'), 'Accept' => 'application/json'];
        $this->withHeaders($headers)->getJson('/api/v1/auth/me')->assertOk();
        $this->withHeaders($headers)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withHeaders($headers)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_wrong_password_and_suspended_accounts_are_rejected(): void
    {
        $user = $this->customer();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nope'])->assertUnprocessable();

        $headers = $this->jwt($user);
        $user->update(['status' => UserStatus::Suspended]);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
        // Existing tokens stop working immediately too.
        $this->withHeaders($headers)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_registration_always_creates_a_customer(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Buyer', 'email' => 'buyer@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'roles' => ['admin'],
        ])->assertCreated()->assertJsonPath('data.attributes.roles', ['customer']);
    }

    public function test_only_staff_with_dashboard_permission_can_sign_in_to_the_admin_panel(): void
    {
        $owner = $this->owner();
        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $admin = $this->admin();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/admin')->assertOk();
    }

    public function test_custom_role_grants_exactly_the_selected_permissions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Support Agent',
            'permissions' => [PermissionName::DashboardView->value, PermissionName::OrdersView->value],
        ])->assertRedirect('/admin/roles');

        $role = Role::findByName('support_agent', 'web');
        $this->assertEqualsCanonicalizing(['dashboard.view', 'orders.view'], $role->permissions->pluck('name')->all());

        $agent = User::factory()->create()->assignRole($role);

        $this->actingAs($agent)->get('/admin/orders')->assertOk();
        $this->actingAs($agent)->get('/admin/products')->assertForbidden();
        $this->actingAs($agent)->get('/admin/roles')->assertForbidden();
        $this->actingAs($agent)->delete("/admin/shops/{$this->shopFor(null)->slug}")->assertForbidden();
    }

    public function test_system_roles_cannot_be_deleted_and_last_admin_is_protected(): void
    {
        $admin = $this->admin();
        $adminRole = Role::findByName('admin', 'web');

        // Even the super-admin (who passes every policy) is stopped by the action itself.
        $this->actingAs($admin)->delete("/admin/roles/{$adminRole->id}")->assertSessionHasErrors('role');
        $this->assertNotNull(Role::findByName('admin', 'web'));

        // A user holding only roles.delete (not super-admin) is stopped by the policy.
        $manager = User::factory()->create();
        $manager->givePermissionTo([PermissionName::DashboardView->value, PermissionName::RolesView->value, PermissionName::RolesDelete->value]);
        $this->actingAs($manager)->delete('/admin/roles/'.Role::findByName('shop_owner', 'web')->id)->assertForbidden();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'status' => 'active', 'roles' => ['customer'],
        ])->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_admin_creates_shop_owner_and_shop_through_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Owner', 'email' => 'new-owner@example.com', 'status' => 'active',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'roles' => ['shop_owner'],
        ])->assertRedirect('/admin/users');

        $owner = User::where('email', 'new-owner@example.com')->firstOrFail();
        $this->assertTrue($owner->hasRole('shop_owner'));

        $this->actingAs($admin)->post('/admin/shops', [
            'name' => ['en' => 'Owner Shop', 'bn' => 'মালিকের দোকান'], 'owner_id' => $owner->id, 'status' => 'active',
        ])->assertRedirect('/admin/shops');

        $this->assertTrue($owner->shops()->where('slug', 'owner-shop')->exists());

        // A customer can never be assigned as a shop owner.
        $this->actingAs($admin)->post('/admin/shops', [
            'name' => ['en' => 'Bad Shop'], 'owner_id' => $this->customer()->id, 'status' => 'active',
        ])->assertSessionHasErrors('owner_id');
    }
}
