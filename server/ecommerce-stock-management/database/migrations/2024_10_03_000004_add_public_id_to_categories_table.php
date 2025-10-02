<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        $connection = \Illuminate\Support\Facades\DB::connection('mongodb');
        $collection = $connection->getCollection('categories');

        $cursor = $collection->find(['public_id' => ['$exists' => false]]);

        foreach ($cursor as $document) {
            $collection->updateOne(
                ['_id' => $document['_id']],
                ['$set' => ['public_id' => 'cat_' . Str::random(20)]]
            );
        }

        $collection->createIndex(['public_id' => 1], [
            'unique' => true,
            'sparse' => true
        ]);
    }

    public function down()
    {
        $connection = \Illuminate\Support\Facades\DB::connection('mongodb');
        $collection = $connection->getCollection('categories');

        try {
            $collection->dropIndex('public_id_1');
        } catch (\Exception $e) {
            // Index might not exist
        }

        $collection->updateMany(
            [],
            ['$unset' => ['public_id' => '']]
        );
    }
};
