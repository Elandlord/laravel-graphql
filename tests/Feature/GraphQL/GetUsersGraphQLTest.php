<?php

namespace Tests\Feature\GraphQL;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class GetUsersGraphQLTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    use WithFaker;
    use MakesGraphQLRequests;

    const USER_COUNT = 10;
    const USERS_PER_PAGE = 5;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->count(self::USER_COUNT)->create();
    }

    /** @test */
    public function it_gets_paginated_users()
    {
        $this->assertDatabaseCount('users', self::USER_COUNT);

        $response = $this->getUsers();

        $response->assertSuccessful();

        $paginatedResult = json_decode($response->getContent())->data->users;

        $this->assertCount(self::USERS_PER_PAGE, $paginatedResult->data);

        $this->assertEquals(1, $paginatedResult->paginatorInfo->currentPage);
        $this->assertEquals(2, $paginatedResult->paginatorInfo->lastPage);
    }

    /** @test */
    public function it_gets_next_page()
    {
        $response = $this->getUsers(2);

        $response->assertSuccessful();

        $paginatedResult = json_decode($response->getContent())->data->users;

        $this->assertCount(self::USERS_PER_PAGE, $paginatedResult->data);

        $this->assertEquals(2, $paginatedResult->paginatorInfo->currentPage);
        $this->assertEquals(2, $paginatedResult->paginatorInfo->lastPage);
    }

    public function getUsers(int $page = 1): TestResponse
    {
        return $this->graphQL(/** @lang GraphQL */"
        {
          users(page: {$page}) {
              data {
                  id
                  name
              }
              paginatorInfo {
                  currentPage
                  lastPage
              }
          }
        }
        ");
    }
}
