# AWS Deployment & Production Guide

## 🚀 AWS Deployment Section

### S3 Configuration

- ✅ Complete `.env` setup for AWS credentials
- ✅ S3 bucket policy reference (from your aws file)
- ✅ Auto-upload pattern via `Product::uploadImages()`
- ✅ Local fallback configuration

### Backend Deployment Options

#### EC2/Elastic Beanstalk
- Step-by-step environment setup
- Dependencies management
- Permissions configuration
- Web server setup

#### Docker on ECS/Fargate
- Container orchestration guidance using existing `docker-compose.yml`
- Process Management: Supervisor/PM2 for queue workers

### Frontend Deployment Options

#### S3 + CloudFront
- Static hosting with CDN

#### Vercel/Netlify
- Alternative platform deployments

#### Build Configuration
- API URL updates for production

---

## ⚠️ Social Authentication Section

### Current Status Documentation

**Warning:** Not production-ready

#### What's Already in Place
- Controller routes and Socialite package implemented

#### Known Issues (7 specific TODOs)
- ❌ Frontend callback handling missing
- ❌ Provider credentials not configured
- ❌ Callback URL routing incomplete
- ❌ Token generation needs testing
- ⚠️ User model fields exist but may need migration

### Implementation Roadmap

5-step guide for correctly implementing social auth:

1. Environment variable configuration
2. Services config setup
3. Frontend callback page completion
4. OAuth flow testing
5. Edge case handling (existing emails, provider switching, account linking)

---

## 🎯 Database Seeding Strategy

### Complete Seeder Documentation

#### Available Seeders (in dependency order)
1. `AdminUserSeeder`
2. `CategorySeeder`
3. `ProductSeeder`
4. `CustomerSeeder`
5. `InventorySeeder`

#### Default Credentials
- Email: `admin@example.com`
- Password: `admin123`

### Seeding Commands

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=AdminUserSeeder

# Fresh migration + seed (⚠️ WARNING: Drops all data)
php artisan migrate:fresh --seed
```

### Key Patterns Documented

- **Idempotency**: Uses `updateOrCreate()` for safe re-runs
- **Environment-specific strategies**:
    - Local/Staging: Full test data
    - Production: Never auto-seed
- **Security**: Manual admin creation in production

---

## 🚀 CI/CD Pipeline (GitHub Actions)

### Complete Workflow Structure

#### 4 Jobs Pipeline
1. **Backend Tests**: PHP 8.2 + MongoDB extension setup
2. **Frontend Build**: Node 18 build process
3. **Backend Deploy (ECS)**: Docker build & push to AWS ECR
4. **Frontend Deploy (S3)**: S3 sync + CloudFront invalidation

### GitHub Secrets Required

```yaml
AWS_ACCESS_KEY_ID         # IAM credentials
AWS_SECRET_ACCESS_KEY     # IAM secret
S3_BUCKET                 # Frontend hosting bucket
CLOUDFRONT_DISTRIBUTION_ID # CDN distribution
```

### Pipeline Features

- ✅ Automated testing before deployment
- ✅ Branch protection (only deploys from `main`)
- ✅ Artifact management between jobs
- ✅ Cache invalidation for instant updates
- ✅ Zero-downtime ECS rolling deployment

---

## 📊 Monitoring & Logging (AWS)

### CloudWatch Logs Integration

#### Backend Configuration

Add to `config/logging.php`:

```php
'cloudwatch' => [
        'driver' => 'monolog',
        'handler' => \Aws\CloudWatchLogs\Handler\CloudWatchLogsHandler::class,
        // ... (configuration)
],
```

#### Environment Variables

```env
LOG_CHANNEL=stack
LOG_STACK=daily,cloudwatch
CLOUDWATCH_LOG_GROUP=/aws/ecs/esm-backend
```

#### ECS Task Definition

```json
{
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "/aws/ecs/esm-backend"
        }
    }
}
```

### Application Performance Monitoring

#### AWS Services Stack
- **CloudWatch Application Insights**: Auto-discovery of issues
- **X-Ray**: Distributed tracing for API requests
- **CloudWatch Alarms**: Resource and performance alerts

#### X-Ray Integration

```bash
composer require aws/aws-xray-sdk-php
```

Add middleware in `bootstrap/app.php`.

#### Alert Thresholds
- CPU >80%
- Errors >10/min
- Response time >500ms (p95)
- MongoDB connection failures

### Monitoring Dashboard

#### Key Metrics Categories

1. **Backend Metrics**
     - Request rate (requests/second)
     - Response time (p50, p95, p99)
     - Error rate (4xx, 5xx)
     - Resource utilization

2. **Frontend Metrics**
     - Cache hit ratio
     - CDN performance
     - Data transfer costs

3. **Database Metrics**
     - Query performance
     - Connection count
     - Slow queries (>100ms)

### CloudWatch Insights Queries

#### Find Slow API Requests (>500ms)
```
fields @timestamp, @message, @duration
| filter @message like /response time/
| filter @duration > 500
| sort @duration desc
```

#### Error Rate Trending
```
fields @timestamp, @message
| filter @message like /ERROR/
| stats count() by bin(5m)
```

#### JWT Authentication Failures
```
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
- Performance degradation (response time >1s)
- Upload failures
- Error rate >5%

---

## 📋 Summary

### Production-Ready Documentation Includes

✅ **Database Seeding**: Complete seeder documentation with commands and patterns  
✅ **GitHub Actions CI/CD**: Full deployment pipeline with 4 jobs  
✅ **AWS Monitoring**: CloudWatch Logs, X-Ray tracing, alerting strategy  
✅ **Production-Ready Patterns**: Environment-specific configurations  
✅ **Security Considerations**: Never auto-seed production, proper alert thresholds

### Complete Development Lifecycle Coverage

- Development workflows
- Testing strategies
- Deployment automation
- Performance monitoring
- Incident response

**Result**: AI agents working with this codebase now have complete visibility into the entire development lifecycle from local setup through production monitoring! 🎉
