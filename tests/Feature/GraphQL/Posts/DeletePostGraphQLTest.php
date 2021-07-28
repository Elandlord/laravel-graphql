<?php

namespace Tests\Feature\GraphQL\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class DeletePostGraphQLTest extends TestCase
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

        $this->post = $this->user->posts()->create([
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
        ]);

        $this->data = [
            'id' => $this->post->id,
        ];
    }

    /** @test */
    public function it_updates_post()
    {
        $this->assertDatabaseCount('posts', 1);

        $response = $this->deletePost($this->data);

        $this->assertDatabaseCount('posts', 0);

        $response->assertSuccessful();

        $this->assertDatabaseMissing('posts', $this->data);
    }

    /** @test */
    public function it_can_only_delete_own_posts()
    {
        $newUser = User::factory()->create();

        $newPost = $newUser->posts()->create([
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
        ]);

        $this->data['id'] = $newPost->id;

        $this->assertDatabaseCount('posts', 2);

        $response = $this->deletePost($this->data);

        $this->assertDatabaseCount('posts', 2);

        $data = json_decode($response->getContent());

        $this->assertEquals('This action is unauthorized.', $data->errors[0]->message);
    }

    /** @test */
    public function it_requires_login()
    {
        $response = $this->deletePost($this->data, false);

        $data = json_decode($response->getContent());

        $this->assertEquals('Unauthenticated.', $data->errors[0]->message);
    }

    public function deletePost(array $data, bool $addAuthorization = true): TestResponse
    {
        $authorization = [];

        if($addAuthorization) {
            $authorization['Authorization'] = sprintf('Bearer %s', $this->token->plainTextToken);
        }

        return $this->withHeaders($authorization)->graphQL(/** @lang GraphQL */'
            mutation DeletePost($id: ID!) {
              deletePost(id: $id) {
                id
                title
                content
              }
            }
        ', $data);
    }
}
