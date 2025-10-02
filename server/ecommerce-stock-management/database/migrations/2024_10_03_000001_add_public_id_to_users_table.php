<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        // ✅ Use MongoDB's native query instead of Eloquent
        $connection = \Illuminate\Support\Facades\DB::connection('mongodb');
        $collection = $connection->getCollection('users');

        // Find all users without public_id and update them
        $cursor = $collection->find(['public_id' => ['$exists' => false]]);

        foreach ($cursor as $document) {
            $collection->updateOne(
                ['_id' => $document['_id']],
                ['$set' => ['public_id' => 'usr_' . Str::random(20)]]
            );
        }

        // Create unique index
        $collection->createIndex(['public_id' => 1], [
            'unique' => true,
            'sparse' => true
        ]);
    }

    public function down()
    {
        $connection = \Illuminate\Support\Facades\DB::connection('mongodb');
        $collection = $connection->getCollection('users');

        // Drop index
        try {
            $collection->dropIndex('public_id_1');
        } catch (\Exception $e) {
            // Index might not exist
        }

        // Remove public_id field
        $collection->updateMany(
            [],
            ['$unset' => ['public_id' => '']]
        );
    }
};
