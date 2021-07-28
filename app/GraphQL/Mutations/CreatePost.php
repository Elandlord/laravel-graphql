<?php

namespace App\GraphQL\Mutations;

use App\Models\Post;

class CreatePost
{
    public function create($root, array $args): Post
    {
        return Post::create([
            'title' => $args['title'],
            'content' => $args['content'],
            'author_id' => auth()->user()->id,
        ]);
    }
}
