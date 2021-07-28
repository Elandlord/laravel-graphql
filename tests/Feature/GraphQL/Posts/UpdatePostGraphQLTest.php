<?php

namespace Tests\Feature\GraphQL\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class UpdatePostGraphQLTest extends TestCase
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
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
        ];
    }

    /** @test */
    public function it_updates_post()
    {
        $response = $this->updatePost($this->data);

        $response->assertSuccessful();

        $this->assertDatabaseHas('posts', array_merge($this->data, [
            'author_id' => $this->user->id,
        ]));
    }

    /** @test */
    public function it_can_only_update_own_posts()
    {
        $newUser = User::factory()->create();

        $newPost = $newUser->posts()->create([
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
        ]);

        $this->data['id'] = $newPost->id;

        $response = $this->updatePost($this->data);

        $data = json_decode($response->getContent());

        $this->assertEquals('This action is unauthorized.', $data->errors[0]->message);
    }

    /** @test */
    public function it_requires_login()
    {
        $response = $this->updatePost($this->data, false);

        $data = json_decode($response->getContent());

        $this->assertEquals('Unauthenticated.', $data->errors[0]->message);
    }

    public function updatePost(array $data, bool $addAuthorization = true): TestResponse
    {
        $authorization = [];

        if($addAuthorization) {
            $authorization['Authorization'] = sprintf('Bearer %s', $this->token->plainTextToken);
        }

        return $this->withHeaders($authorization)->graphQL(/** @lang GraphQL */'
            mutation UpdatePost($id: ID!, $title: String!, $content: String!) {
              updatePost(id: $id, input: {
                title: $title,
                content: $content,
              }) {
                id
                title
                content
              }
            }
        ', $data);
    }
}
