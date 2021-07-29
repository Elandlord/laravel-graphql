<?php

namespace Tests\Feature\GraphQL\Me;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CurrentUserGraphQLTest extends TestCase
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
    }

    /** @test */
    public function it_fetches_user()
    {
        $response = $this->getCurrentUser();

        $response->assertSuccessful();

        $data = json_decode($response->getContent())->data;

        $this->assertTrue(property_exists($data, 'me'));

        $this->assertEquals($this->user->name, $data->me->name);
        $this->assertEquals($this->user->email, $data->me->email);

        $postsFromData = collect($data->me->posts);

        $userPosts = $this->user->posts()
            ->select('id', 'title', 'content')
            ->get();

        $userPosts->each( fn($post) => $postsFromData->contains($post));
    }

    /** @test */
    public function it_requires_login()
    {
        $response = $this->getCurrentUser(false);

        $data = json_decode($response->getContent());

        $this->assertEquals('Unauthenticated.', $data->errors[0]->message);
    }

    public function getCurrentUser(bool $addAuthorization = true): TestResponse
    {
        $authorization = [];

        if($addAuthorization) {
            $authorization['Authorization'] = sprintf('Bearer %s', $this->token->plainTextToken);
        }

        return $this->withHeaders($authorization)->graphQL(/** @lang GraphQL */'
            {
              me {
                name
                email
                posts {
                    id
                    title
                    content
                }
              }
            }
        ');
    }
}
