<?php

namespace Tests\Feature\GraphQL\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class UpdateUserGraphQLTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    use WithFaker;
    use MakesGraphQLRequests;

    const TOKEN_NAME = 'test-token';

    public $user;
    public $token;
    public $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken(self::TOKEN_NAME);

        $this->data = [
            'id' => $this->user->id,
            'name' => $this->faker->name,
            'password' => $this->faker->word . $this->faker->numberBetween(100, 999)
        ];
    }

    /** @test */
    public function it_can_update_user()
    {
        $response = $this->updateUser($this->data);

        $response->assertSuccessful();

        $data = json_decode($response->getContent());

        $this->assertObjectNotHasAttribute('errors', $data);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => $this->data['name'],
        ]);

        $updatedUser = User::first();
        $this->assertTrue(Hash::check($this->data['password'], $updatedUser->password));
    }

    /** @test */
    public function it_can_omit_password()
    {
        $newPassword = $this->data['password'];
        unset($this->data['password']);
        $response = $this->updateUser($this->data);

        $response->assertSuccessful();

        $updatedUser = User::first();
        $this->assertFalse(Hash::check($newPassword, $updatedUser->password));
    }

    /** @test */
    public function it_can_only_update_itself()
    {
        $otherUser = User::factory()->create();

        $this->data['id'] = $otherUser->id;

        $response = $this->updateUser($this->data);

        $data = json_decode($response->getContent());

        $this->assertEquals('This action is unauthorized.', $data->errors[0]->message);
    }

    /** @test */
    public function it_requires_login()
    {
        $response = $this->updateUser($this->data, false);

        $data = json_decode($response->getContent());

        $this->assertEquals('Unauthenticated.', $data->errors[0]->message);
    }

    public function updateUser(array $data, bool $addAuthorization = true): TestResponse
    {
        $authorization = [];

        if($addAuthorization) {
            $authorization['Authorization'] = sprintf('Bearer %s', $this->token->plainTextToken);
        }

        return $this->withHeaders($authorization)->graphQL(/** @lang GraphQL */'
            mutation UpdateUser($id: ID!, $name: String!, $password: String!) {
              updateUser(id: $id, input: {
                name: $name,
                password: $password
              }) {
                id
                name
              }
            }
        ', $data);
    }
}
