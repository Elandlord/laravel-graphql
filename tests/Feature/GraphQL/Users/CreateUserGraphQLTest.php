<?php

namespace Tests\Feature\GraphQL\Users;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CreateUserGraphQLTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    use WithFaker;
    use MakesGraphQLRequests;

    /** @test */
    public function it_creates_user()
    {
        $name = $this->faker->name;
        $email = $this->faker->email;
        $password = $this->faker->word;

        $data = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ];

        $this->assertDatabaseCount('users', 0);

        $response = $this->graphQL(/** @lang GraphQL */'
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

        $response->assertSuccessful();

        $this->assertDatabaseCount('users', 1);

        $this->assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }
}
