<?php

namespace Tests\Feature\GraphQL\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CreatePostGraphQLTest extends TestCase
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
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
        ];
    }

    /** @test */
    public function it_creates_post()
    {
        $this->assertDatabaseCount('posts', 0);

        $response = $this->createPost($this->data);

        $response->assertSuccessful();

        $this->assertDatabaseCount('posts', 1);

        $this->assertDatabaseHas('posts', array_merge($this->data, [
            'author_id' => $this->user->id,
        ]));
    }

    /** @test */
    public function it_requires_login()
    {
        $response = $this->createPost($this->data, false);

        $data = json_decode($response->getContent());

        $this->assertEquals('Unauthenticated.', $data->errors[0]->message);
    }

    public function createPost(array $data, bool $addAuthorization = true): TestResponse
    {
        $authorization = [];

        if($addAuthorization) {
            $authorization['Authorization'] = sprintf('Bearer %s', $this->token->plainTextToken);
        }

        return $this->withHeaders($authorization)->graphQL(/** @lang GraphQL */'
            mutation CreatePost($title: String!, $content: String!) {
              createPost(input: {
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
