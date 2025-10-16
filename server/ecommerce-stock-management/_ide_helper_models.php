<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property string $id 4 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 4 occurrences
 * @property string|null $description 4 occurrences
 * @property string|null $image_url 2 occurrences
 * @property string|null $meta_description 2 occurrences
 * @property string|null $meta_title 2 occurrences
 * @property string|null $name 4 occurrences
 * @property string|null $public_id 4 occurrences
 * @property string|null $slug 4 occurrences
 * @property int|null $sort_order 4 occurrences
 * @property string|null $status 4 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 4 occurrences
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read int|null $children_count
 * @property-read Category|null $parent
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category active()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereImageUrl($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereMetaDescription($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereMetaTitle($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category wherePublicId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereSortOrder($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereStatus($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id 7 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 7 occurrences
 * @property string|null $first_name 7 occurrences
 * @property string|null $last_name 7 occurrences
 * @property bool|null $marketing_consent 7 occurrences
 * @property string|null $phone 7 occurrences
 * @property string|null $public_id 2 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 7 occurrences
 * @property string|null $user_id 7 occurrences
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \App\Models\User|null $user
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereFirstName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereLastName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereMarketingConsent($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer wherePublicId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Customer whereUserId($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id 5 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 5 occurrences
 * @property \Illuminate\Support\Carbon|null $last_movement_date 5 occurrences
 * @property string|null $location_id 5 occurrences
 * @property string|null $product_id 5 occurrences
 * @property int|null $qty_available 5 occurrences
 * @property int|null $qty_on_hand 5 occurrences
 * @property int|null $qty_reserved 5 occurrences
 * @property int|null $reorder_level 5 occurrences
 * @property int|null $reorder_quantity 5 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 5 occurrences
 * @property-read \App\Models\Product|null $product
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereLastMovementDate($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereLocationId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereProductId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereQtyAvailable($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereQtyOnHand($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereQtyReserved($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereReorderLevel($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereReorderQuantity($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Inventory whereUpdatedAt($value)
 */
	class Inventory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Customer|null $customer
 * @property-read mixed $id
 * @property-read \App\Models\User|null $user
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order byDateRange($from, $to)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order completed()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order pending()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order processing()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Order vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * @property mixed $id 4 occurrences
 * @property array<array-key, mixed>|null $abilities 4 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 4 occurrences
 * @property \Illuminate\Support\Carbon|null $last_used_at 1 occurrences
 * @property string|null $name 4 occurrences
 * @property string|null $token 4 occurrences
 * @property string|null $tokenable_id 4 occurrences
 * @property string|null $tokenable_type 4 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 4 occurrences
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $tokenable
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereAbilities($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereLastUsedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereToken($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereTokenableId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereTokenableType($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PersonalAccessToken whereUpdatedAt($value)
 */
	class PersonalAccessToken extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id 16 occurrences
 * @property string|null $category 16 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 16 occurrences
 * @property string|null $description 16 occurrences
 * @property array<array-key, mixed>|null $images 15 occurrences
 * @property string|null $meta_description 7 occurrences
 * @property string|null $meta_title 7 occurrences
 * @property string|null $name 16 occurrences
 * @property numeric|null $price 16 occurrences
 * @property string|null $public_id 16 occurrences
 * @property string|null $sku 16 occurrences
 * @property string|null $status 16 occurrences
 * @property int|null $stock_quantity 16 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 16 occurrences
 * @property numeric|null $weight 13 occurrences
 * @property-read \App\Models\Category|null $category_model
 * @property-read mixed $has_images
 * @property-read mixed $image_count
 * @property-read mixed $image_urls
 * @property-read mixed $is_in_stock
 * @property-read mixed $is_low_stock
 * @property-read mixed $primary_image
 * @property-read \App\Models\Inventory|null $inventory
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product active()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product byIds(array $ids)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product inStock()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product lowStock($threshold = 10)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereCategory($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereImages($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereMetaDescription($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereMetaTitle($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product wherePrice($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product wherePublicId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereStockQuantity($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Product whereWeight($value)
 */
	class Product extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id 9 occurrences
 * @property string|null $avatar 1 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 9 occurrences
 * @property string|null $email 9 occurrences
 * @property \Illuminate\Support\Carbon|null $email_verified_at 9 occurrences
 * @property string|null $name 9 occurrences
 * @property string|null $password 9 occurrences
 * @property string|null $provider 1 occurrences
 * @property string|null $provider_id 1 occurrences
 * @property string|null $public_id 1 occurrences
 * @property string|null $role 9 occurrences
 * @property string|null $status 9 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 9 occurrences
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User search(\MongoDB\Builder\Type\SearchOperatorInterface|array $operator, ?string $index = null, ?array $highlight = null, ?bool $concurrent = null, ?string $count = null, ?string $searchAfter = null, ?string $searchBefore = null, ?bool $scoreDetails = null, ?array $sort = null, ?bool $returnStoredSource = null, ?array $tracking = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User vectorSearch(string $index, string $path, array $queryVector, int $limit, bool $exact = false, \MongoDB\Builder\Type\QueryInterface|array $filter = [], ?int $numCandidates = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereProvider($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereProviderId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User wherePublicId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereRole($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Tymon\JWTAuth\Contracts\JWTSubject {}
}

