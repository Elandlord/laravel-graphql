<?php

namespace Tests\Feature\GraphQL\Users;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CreateUserGraphQLTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    use WithFaker;
    use MakesGraphQLRequests;

    public $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $this->faker->word . $this->faker->word . $this->faker->word,
        ];
    }

    /** @test */
    public function it_creates_user()
    {
        $this->assertDatabaseCount('users', 0);

        $response = $this->createUser($this->data);

        $response->assertSuccessful();

        $this->assertDatabaseCount('users', 1);

        $this->assertDatabaseHas('users', [
            'name' => $this->data['name'],
            'email' => $this->data['email'],
        ]);
    }

    /** @test */
    public function it_cannot_insert_duplicate_email()
    {
        $this->createUser($this->data);

        $repeatedResponse = $this->createUser($this->data);

        $repeatedResponse->assertSuccessful();

        $errorResponse = json_decode($repeatedResponse->getContent());

        $this->assertObjectHasAttribute('errors', $errorResponse);
        $this->assertCount(1, $errorResponse->errors);

        $this->assertEquals(
            "The input.email has already been taken.",
            $errorResponse->errors[0]->extensions->validation->{"input.email"}[0]
        );
    }

    public function createUser(array $data): TestResponse
    {
        return $this->graphQL(/** @lang GraphQL */'
            mutation CreateUser($name: String!, $email: String!, $password: String!) {
              createUser(input: {
                name: $name,
                email: $email,
                password: $password
              }) {
                id
                name
                email
              }
            }
        ', $data);
    }
}
