<?php

namespace Tests\Feature\GraphQL\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class DeleteUserGraphQLTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    use WithFaker;
    use MakesGraphQLRequests;

    const TOKEN_NAME = 'test-token';

    public $user;
    public $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken(self::TOKEN_NAME);
    }

    /** @test */
    public function it_can_delete_user()
    {
        $response = $this->deleteUser($this->user->id);

        $response->assertSuccessful();

        $data = json_decode($response->getContent());

        $this->assertObjectNotHasAttribute('errors', $data);

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    /** @test */
    public function it_can_only_delete_itself()
    {
        $otherUser = User::factory()->create();

        $response = $this->deleteUser($otherUser->id);

        $data = json_decode($response->getContent());

        $this->assertEquals('This action is unauthorized.', $data->errors[0]->message);
    }

    /** @test */
    public function it_requires_login()
    {
        $response = $this->deleteUser($this->user->id, false);

        $data = json_decode($response->getContent());

        $this->assertEquals('Unauthenticated.', $data->errors[0]->message);
    }

    public function deleteUser(int $user_id, bool $addAuthorization = true): TestResponse
    {
        $authorization = [];

        if($addAuthorization) {
            $authorization['Authorization'] = sprintf('Bearer %s', $this->token->plainTextToken);
        }

        return $this->withHeaders($authorization)->graphQL(/** @lang GraphQL */'
            mutation DeleteUser($id: ID!) {
              deleteUser(id: $id) {
                id
                name
                email
              }
            }
        ', [
            'id' => $user_id,
        ]);
    }
}
