<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InertiaPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public static function rolePages(): array
    {
        return [
            ['MARKETING', '/marketing/dashboard', 'marketing/MarketingDashboard'],
            ['DIREKTUR', '/director/dashboard', 'director/DirectorDashboard'],
            ['ADMINISTRASI', '/administration/spj', 'administration/AdministrationSpj'],
            ['WAREHOUSE', '/warehouse/fulfillment', 'warehouse/WarehouseFulfillment'],
            ['FINANCE', '/finance/purchases', 'finance/FinancePurchases'],
            ['SUPPLIER', '/supplier/portal', 'supplier/SupplierPortalView'],
        ];
    }

    #[DataProvider('rolePages')]
    public function test_each_role_receives_its_inertia_page(string $role, string $url, string $component): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component($component)->where('auth.user.id', $user->id)
            ->where('auth.user.role', $role)->missing('auth.user.password')->missing('auth.user.remember_token')
            ->where('navigation.role', $role)->etc());
    }

    #[DataProvider('rolePages')]
    public function test_protected_pages_redirect_guests_to_login(string $role, string $url, string $component): void
    {
        $this->get($url)->assertRedirect(route('login', ['redirect' => $url]));
    }

    public function test_guest_portal_and_login_render_inertia_without_a_token(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('auth/LoginView')->where('auth.user', null)->etc());
        $this->get('/portal?tab=tracking&resi=RESI-123')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('client/PublicPortalView')->where('auth.user', null)->url('/portal?resi=RESI-123&tab=tracking')->etc());
    }

    public function test_wrong_role_cannot_receive_another_divisions_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'MARKETING']))
            ->get('/finance/purchases')->assertRedirect('/marketing/dashboard');
        $this->get('/supplier/portal')->assertRedirect('/marketing/dashboard');
    }

    public function test_edit_parameters_and_query_context_reach_the_inertia_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'MARKETING']))
            ->get('/marketing/orders/99/edit?fromOrderId=ORD-123')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('marketing/OrderFormView')->where('navigation.params.id', '99')
            ->url('/marketing/orders/99/edit?fromOrderId=ORD-123')->etc());
    }

    public function test_inertia_navigation_returns_page_json_instead_of_static_spa_html(): void
    {
        $user = User::factory()->create(['role' => 'FINANCE']);
        $initial = $this->actingAs($user)->get('/finance/purchases');
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $initial->viewData('page')['version'] ?? ''])
            ->get('/finance/purchases/create?requestCode=REQ-123&supplier=Vendor')
            ->assertOk()->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'finance/FinancePurchaseCreate')
            ->assertJsonPath('url', '/finance/purchases/create?requestCode=REQ-123&supplier=Vendor');
    }

    public function test_legacy_links_keep_their_destination_tab(): void
    {
        $this->get('/director/finance')->assertRedirect('/director/monitoring?tab=finance');
        $this->get('/warehouse/requests')->assertRedirect('/warehouse/fulfillment?tab=requests');
        $this->get('/client/payments')->assertRedirect('/portal?tab=tracking');
        $this->get('/supplier/shipments')->assertRedirect('/supplier/portal?tab=shipments');
    }

    public function test_web_login_creates_a_session_and_keeps_transaction_api_authentication(): void
    {
        $user = User::factory()->create(['email' => 'finance@example.test', 'password' => 'correct-password', 'role' => 'FINANCE']);
        $response = $this->postJson('/login', ['email' => $user->email, 'password' => 'correct-password']);
        $response->assertOk()->assertJsonPath('user.role', 'FINANCE')->assertJsonStructure(['token']);
        $this->assertAuthenticatedAs($user, 'web');
        $this->get('/finance/purchases')->assertOk();
        $this->getJson('/api/auth/user')->assertOk()->assertJsonPath('user.id', $user->id);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'web-api']);
    }

    public function test_invalid_web_login_never_grants_a_local_fallback_role(): void
    {
        User::factory()->create(['email' => 'finance@example.test', 'password' => 'correct-password', 'role' => 'FINANCE']);
        $this->postJson('/login', ['email' => 'finance@example.test', 'password' => 'wrong-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest('web');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->get('/finance/purchases')->assertRedirect(route('login', ['redirect' => '/finance/purchases']));
    }

    public function test_web_logout_ends_the_session_and_revokes_only_the_presented_token(): void
    {
        $user = User::factory()->create(['email' => 'supplier@example.test', 'password' => 'correct-password', 'role' => 'SUPPLIER']);
        $otherToken = $user->createToken('other-session');
        $token = $this->postJson('/login', ['email' => $user->email, 'password' => 'correct-password'])->json('token');
        $this->withToken($token)->postJson('/logout')->assertOk();
        $this->assertGuest('web');
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
        $this->get('/supplier/portal')->assertRedirect(route('login', ['redirect' => '/supplier/portal']));
    }
}
