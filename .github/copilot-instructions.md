# Copilot Instructions for Innovior IOT ESM

## Architecture Overview

**Monorepo Structure**: E-commerce stock management system with separated `client/` (React/TypeScript SPA) and `server/` (Laravel API) directories.

### Backend: Laravel + MongoDB + JWT

- **Database**: MongoDB (NOT MySQL/PostgreSQL). All models extend `MongoDB\Laravel\Eloquent\Model` and use `protected $connection = 'mongodb'`
- **Authentication**: Custom JWT implementation (tymon/jwt-auth) with role-based access (`admin`, `customer`)
- **Middleware Stack** (see `bootstrap/app.php`):
  - Global: CORS, input sanitization, request size validation
  - API: `SanitizeInput`, rate limiting (`:api` throttle), JWT auth via `jwt.auth` alias
  - Route Protection: `jwt.auth` → `admin` middleware chain for admin endpoints
- **Security Pattern**: All user input passes through `QuerySanitizer::sanitize()` to strip MongoDB injection operators (`$where`, `$regex`, etc.)

### Frontend: React + Redux Toolkit + RTK Query

- **State**: Redux with `redux-persist` for auth/cart. Store configured in `src/store/index.ts`
- **API Layer**: RTK Query APIs (`authApi`, `adminApi`, `productsApi`) in `src/store/api/`
- **Auth Flow**: JWT token stored in Redux + persisted to localStorage, attached via `prepareHeaders` in baseQuery
- **Entry**: `src/main.tsx` → Redux `Provider` → `PersistGate` → `RouterProvider`

## Critical Developer Workflows

### Backend Setup

```bash
cd server/ecommerce-stock-management
composer install
php artisan jwt:secret          # Generate JWT secret (required first time)
php artisan migrate             # MongoDB collections
php artisan serve               # http://localhost:8000
```

### Frontend Setup

```bash
cd client/shop-sync-react
npm install
npm run dev                     # http://localhost:3000
```

### Docker (Full Stack)

```bash
cd server
docker-compose up -d            # Laravel + Redis
# Frontend runs separately: cd ../client/shop-sync-react && npm run dev
```

## Project-Specific Patterns

### Backend Routes Structure (`routes/api.php`)

- **Public**: `/api/products`, `/api/categories` (no auth)
- **Auth**: `/api/auth/*` with `throttle:auth` rate limiting
- **Admin**: `/api/admin/*` requires `jwt.auth` + `admin` middleware
- **Customer**: `/api/customer/*` requires `jwt.auth` only

### Adding New API Endpoints

1. **Controller**: Create in `app/Http/Controllers/Api/` (use `Api` namespace)
2. **Route**: Add to `routes/api.php` with appropriate middleware
3. **Model**: If needed, extend `MongoDB\Laravel\Eloquent\Model`, set `$connection = 'mongodb'`
4. **Validation**: Use `QuerySanitizer::sanitize()` for any user input
5. **Frontend**: Add RTK Query endpoint in relevant `src/store/api/*Api.ts`

### Image Upload Pattern (see `Product::uploadImages()`)

- Validation via `ImageValidator::validate()` (10MB limit, 5 images max, whitelist MIME types)
- Storage: Local `public/images/products/` or S3 (configurable)
- Multiple images stored as array in `images` field
- Use `POST` (not `PUT`) for file uploads with `multipart/form-data`

### Security Requirements

- **Always sanitize**: Wrap user input in `QuerySanitizer::sanitize($value)` before queries
- **Token Blacklist**: Logout adds tokens to `jwt_blacklist` collection (checked in `JWTMiddleware`)
- **Rate Limiting**: Auth routes use `throttle:auth`, admin routes use default `:api` throttle

## Integration Points

### Frontend → Backend API

- **Base URL**: `http://localhost:8000/api` (hardcoded in RTK Query baseQuery)
- **Auth Header**: `Authorization: Bearer {token}` (auto-attached by RTK Query)
- **Response Format**: `{ success: boolean, message: string, data?: any }`

### Redux Store Structure

```typescript
{
  auth: { token, user, isAuthenticated },  // Persisted
  cart: { items, total, itemCount },       // Persisted
  authApi: { ... },                        // RTK Query cache
  adminApi: { ... },                       // RTK Query cache
  productsApi: { ... }                     // RTK Query cache
}
```

## Key Files & Patterns

### Backend

- `bootstrap/app.php`: Middleware configuration (Laravel 11 style)
- `app/Services/QuerySanitizer.php`: MongoDB injection prevention
- `app/Services/ImageValidator.php`: File upload validation
- `app/Traits/MongoIdHelper.php`: Helpers for MongoDB ObjectId handling
- `app/Http/Middleware/JWTMiddleware.php`: Token validation + blacklist check
- `app/Models/*.php`: All use MongoDB connection, not MySQL

### Frontend

- `src/store/index.ts`: Redux store with persistence config
- `src/store/api/*.ts`: RTK Query API definitions (use `createApi`)
- `src/store/slices/*.ts`: Redux slices (auth, cart)
- `src/components/ProtectedRoute.tsx`: Route guard for auth
- `src/components/AdminRoute.tsx`: Route guard for admin role

## Common Pitfalls

1. **MongoDB vs MySQL**: Don't use SQL migrations or raw SQL. Use MongoDB query syntax.
2. **JWT Middleware Alias**: Use `jwt.auth` (not `auth:api` or `auth:sanctum`)
3. **File Uploads**: Always use `POST` (not `PUT`) with `Content-Type: multipart/form-data`
4. **Frontend API Calls**: Never call APIs directly with `fetch`/`axios`—use RTK Query hooks
5. **Input Sanitization**: Backend requires `QuerySanitizer::sanitize()` for user input in controllers
6. **Rate Limiting**: Custom throttle group `throttle:auth` for auth routes (defined in `app.php`)
7. **Social Auth**: Social authentication is NOT production-ready. See "Social Authentication" section before implementing.
8. **S3 Configuration**: Image uploads default to S3. Set `FILESYSTEM_DISK=public` for local development without AWS.

## AWS Deployment

### S3 Configuration (Images/Assets)

- Default filesystem: S3 (see `config/filesystems.php`)
- Configure `.env` with AWS credentials:
  ```env
  AWS_ACCESS_KEY_ID=your_key
  AWS_SECRET_ACCESS_KEY=your_secret
  AWS_DEFAULT_REGION=us-east-1
  AWS_BUCKET=your_bucket_name
  FILESYSTEM_DISK=s3
  ```
- S3 bucket policy: Public read access for product images (see `Docs/10/aws`)
- Image uploads: Auto-uploads to S3 via `Product::uploadImages()` when S3 is configured
- Fallback: Set `FILESYSTEM_DISK=public` for local storage in `storage/app/public`

### Backend Deployment (EC2/Elastic Beanstalk)

1. **Environment Variables**: Copy `.env.example` to `.env` and configure:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - MongoDB connection (`DB_CONNECTION=mongodb`, `DB_URI`)
   - JWT secret (`php artisan jwt:secret`)
   - AWS S3 credentials (above)
2. **Dependencies**: `composer install --optimize-autoloader --no-dev`
3. **Permissions**: Set `storage/` and `bootstrap/cache/` writable
4. **Nginx/Apache**: Point document root to `public/`
5. **Process Manager**: Use supervisor/PM2 for queue workers if using Redis queues

### Frontend Deployment (S3 + CloudFront or Vercel)

1. **Build**: `npm run build` (creates `dist/` folder)
2. **Environment**: Update API base URL in `src/store/api/*Api.ts` to production backend URL
3. **S3 Static Hosting**: Upload `dist/` contents to S3 bucket with static website hosting
4. **CloudFront**: Create distribution pointing to S3 bucket for CDN
5. **Alternative**: Deploy to Vercel/Netlify with build command `npm run build`

### Docker Deployment (AWS ECS/Fargate)

- Use `server/docker-compose.yml` as base for ECS task definitions
- Configure environment variables in ECS task definition
- Set up Application Load Balancer for backend
- Use RDS-compatible MongoDB service or MongoDB Atlas

## Social Authentication (⚠️ Incomplete Implementation)

**Current Status**: Social auth controller exists (`SocialAuthController.php`) but **not fully implemented or tested**.

### What's Currently in Place:

- Routes: `/api/auth/{provider}` and `/api/auth/{provider}/callback`
- Providers: Google, GitHub, Facebook configured in controller
- Flow: OAuth redirect → callback → user creation/linking
- Package: `laravel/socialite` installed

### Known Issues/TODO:

- ❌ Frontend callback handling not implemented
- ❌ Provider credentials not configured in `.env` (need `GOOGLE_CLIENT_ID`, etc.)
- ❌ Callback URL routing between SPA and API not finalized
- ❌ Token generation after social auth needs testing
- ⚠️ User model has `provider` and `provider_id` fields but may need migration

### To Implement Social Auth Correctly:

1. Add provider credentials to `.env`:
   ```env
   GOOGLE_CLIENT_ID=
   GOOGLE_CLIENT_SECRET=
   GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
   ```
2. Configure providers in `config/services.php`
3. Implement frontend callback page (`pages/AuthCallback.tsx` exists but incomplete)
4. Test OAuth flow: redirect → authorize → callback → JWT token return
5. Handle edge cases: existing email, provider switching, account linking

## Testing

- **Backend**: `php artisan test` (in `server/ecommerce-stock-management/`)
- **Frontend**: Test files not yet implemented (placeholder for future)
- **API Testing**: Use `/api/health` for health checks, `/api/test` for basic API verification

## Database Seeding Strategy

### Available Seeders

Located in `database/seeders/`, run in this order for proper dependencies:

1. **AdminUserSeeder**: Creates default admin (`admin@example.com` / `admin123`)
2. **CategorySeeder**: Creates product categories hierarchy
3. **ProductSeeder**: Creates sample products (Nike shoes, books, etc.)
4. **CustomerSeeder**: Creates test customer accounts
5. **InventorySeeder**: Creates inventory records for products

### Seeding Commands

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=AdminUserSeeder

# Fresh migration + seed (⚠️ WARNING: Drops all data)
php artisan migrate:fresh --seed

# Refresh specific seeder (using updateOrCreate pattern)
php artisan db:seed --class=AdminUserSeeder
```

### Seeding Pattern

- All seeders use `updateOrCreate()` for idempotency (safe to run multiple times)
- Default admin credentials: `admin@example.com` / `admin123`
- Customer seeders create users with `customer` role
- Products are linked to categories via category ObjectId

### Environment-Specific Seeding

- **Local/Development**: Use full seed with test data
- **Staging**: Seed admin user only, import real data separately
- **Production**: Never auto-seed; manually create admin via tinker or dedicated script

## CI/CD Pipeline (GitHub Actions)

### Recommended Workflow Structure

Create `.github/workflows/deploy.yml` for automated deployment:

```yaml
name: Deploy to AWS

on:
  push:
    branches: [main, production]
  pull_request:
    branches: [main]

env:
  AWS_REGION: us-east-1
  ECR_REPOSITORY: esm-backend
  ECS_SERVICE: esm-service
  ECS_CLUSTER: esm-cluster
  CONTAINER_NAME: laravel

jobs:
  # Backend Tests
  test-backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          extensions: mongodb
      - name: Install Dependencies
        run: |
          cd server/ecommerce-stock-management
          composer install --no-interaction
      - name: Run Tests
        run: |
          cd server/ecommerce-stock-management
          php artisan test

  # Frontend Build & Test
  build-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: "18"
      - name: Install Dependencies
        run: |
          cd client/shop-sync-react
          npm ci
      - name: Build
        run: |
          cd client/shop-sync-react
          npm run build
      - name: Upload Build Artifacts
        uses: actions/upload-artifact@v3
        with:
          name: frontend-build
          path: client/shop-sync-react/dist

  # Deploy Backend to AWS ECS
  deploy-backend:
    needs: test-backend
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v3
      - name: Configure AWS Credentials
        uses: aws-actions/configure-aws-credentials@v2
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: ${{ env.AWS_REGION }}
      - name: Login to Amazon ECR
        id: login-ecr
        uses: aws-actions/amazon-ecr-login@v1
      - name: Build & Push Docker Image
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          IMAGE_TAG: ${{ github.sha }}
        run: |
          cd server
          docker build -t $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG .
          docker push $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG
      - name: Deploy to ECS
        run: |
          aws ecs update-service \
            --cluster ${{ env.ECS_CLUSTER }} \
            --service ${{ env.ECS_SERVICE }} \
            --force-new-deployment

  # Deploy Frontend to S3 + Invalidate CloudFront
  deploy-frontend:
    needs: build-frontend
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - name: Download Build Artifacts
        uses: actions/download-artifact@v3
        with:
          name: frontend-build
          path: dist
      - name: Configure AWS Credentials
        uses: aws-actions/configure-aws-credentials@v2
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: ${{ env.AWS_REGION }}
      - name: Deploy to S3
        run: |
          aws s3 sync dist/ s3://${{ secrets.S3_BUCKET }} --delete
      - name: Invalidate CloudFront
        run: |
          aws cloudfront create-invalidation \
            --distribution-id ${{ secrets.CLOUDFRONT_DISTRIBUTION_ID }} \
            --paths "/*"
```

### Required GitHub Secrets

Add these to repository settings → Secrets and variables → Actions:

- `AWS_ACCESS_KEY_ID`: IAM user with ECR, ECS, S3, CloudFront permissions
- `AWS_SECRET_ACCESS_KEY`: IAM secret key
- `S3_BUCKET`: Frontend hosting bucket name
- `CLOUDFRONT_DISTRIBUTION_ID`: CloudFront distribution ID

### Pipeline Features

- **Automated Testing**: Runs PHPUnit tests before deployment
- **Docker Build**: Builds and pushes to AWS ECR
- **Zero-Downtime Deployment**: ECS rolling update strategy
- **Frontend Cache Invalidation**: Clears CloudFront cache on deploy
- **Branch Protection**: Only deploys from `main` branch

## Monitoring & Logging (AWS)

### CloudWatch Logs Setup

#### Backend Logging Configuration

Add to `config/logging.php`:

```php
'cloudwatch' => [
    'driver' => 'monolog',
    'handler' => \Aws\CloudWatchLogs\Handler\CloudWatchLogsHandler::class,
    'with' => [
        'client' => new \Aws\CloudWatchLogs\CloudWatchLogsClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]),
        'logGroupName' => env('CLOUDWATCH_LOG_GROUP', '/aws/ecs/esm-backend'),
        'logStreamName' => env('CLOUDWATCH_LOG_STREAM', 'laravel'),
    ],
    'level' => env('LOG_LEVEL', 'info'),
],
```

Update `.env`:

```env
LOG_CHANNEL=stack
LOG_STACK=daily,cloudwatch
CLOUDWATCH_LOG_GROUP=/aws/ecs/esm-backend
CLOUDWATCH_LOG_STREAM=laravel
```

#### ECS Task Definition Logging

Configure in ECS task definition JSON:

```json
{
  "logConfiguration": {
    "logDriver": "awslogs",
    "options": {
      "awslogs-group": "/aws/ecs/esm-backend",
      "awslogs-region": "us-east-1",
      "awslogs-stream-prefix": "ecs"
    }
  }
}
```

### Application Performance Monitoring

#### Recommended AWS Services

1. **CloudWatch Application Insights**: Auto-discovery of application issues
2. **X-Ray**: Distributed tracing for API requests
3. **CloudWatch Alarms**: Set alerts for:
   - High CPU/Memory usage (>80%)
   - HTTP 5xx errors (>10/minute)
   - Response time (>500ms p95)
   - MongoDB connection failures

#### X-Ray Integration (Laravel)

Install AWS X-Ray SDK:

```bash
composer require aws/aws-xray-sdk-php
```

Add middleware in `bootstrap/app.php`:

```php
$middleware->api(prepend: [
    \Aws\XRay\Laravel\Middleware\XRayMiddleware::class,
]);
```

### Monitoring Dashboard

#### Key Metrics to Track

1. **Backend (ECS/EC2)**:

   - Request rate (requests/second)
   - Response time (p50, p95, p99)
   - Error rate (4xx, 5xx)
   - CPU/Memory utilization
   - MongoDB connection pool

2. **Frontend (S3 + CloudFront)**:

   - CloudFront cache hit ratio
   - Origin response time
   - 4xx/5xx error rate
   - Data transfer (GB)

3. **Database (MongoDB Atlas)**:
   - Query performance
   - Connection count
   - Slow queries (>100ms)
   - Disk usage

#### CloudWatch Dashboard JSON

Create custom dashboard with:

- ECS service CPU/Memory graphs
- API Gateway request count
- S3 bucket metrics
- Custom application metrics (login rate, order rate)

### Log Analysis Patterns

#### Useful CloudWatch Insights Queries

```
# Find slow API requests
fields @timestamp, @message, @duration
| filter @message like /response time/
| filter @duration > 500
| sort @duration desc

# Error rate by endpoint
fields @timestamp, @message
| filter @message like /ERROR/
| stats count() by bin(5m)

# JWT authentication failures
fields @timestamp, @message
| filter @message like /Token/
| filter @message like /invalid|expired/
```

### Alerting Strategy

#### Critical Alerts (PagerDuty/SNS)

- API down (health check failing)
- Database connection lost
- Disk space >90%
- Memory >95%

#### Warning Alerts (Slack/Email)

- Response time >1s sustained
- Error rate >5%
- S3 upload failures
- JWT blacklist growing rapidly

## Environment-Specific Notes

- **Production**: Set `APP_ENV=production`, `APP_DEBUG=false`, enable HTTPS middleware
- **Staging**: Use separate MongoDB database and S3 bucket
- **Local**: Use `FILESYSTEM_DISK=public` for local image storage, `APP_DEBUG=true`
