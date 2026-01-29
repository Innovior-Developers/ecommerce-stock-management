# E-Commerce Stock Management System

A comprehensive full-stack e-commerce stock management system built with React, Redux Toolkit, Laravel, and MongoDB. This monorepo contains both the frontend SPA and backend API.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-12.0-red.svg)
![React](https://img.shields.io/badge/React-18.3-blue.svg)
![MongoDB](https://img.shields.io/badge/MongoDB-5.5-green.svg)

## 🌟 Features

### Core Functionality
- **Product Management**: Full CRUD operations for products with image upload support (S3/local storage)
- **Inventory Tracking**: Real-time stock level monitoring with low-stock alerts
- **Category Management**: Hierarchical category system
- **Order Management**: Complete order processing workflow
- **Customer Management**: User registration, authentication, and profile management
- **Payment Integration**: Stripe and PayPal payment gateway support
- **Role-Based Access Control**: Admin and customer roles with JWT authentication

### Technical Features
- **JWT Authentication**: Custom JWT implementation with token blacklisting
- **Rate Limiting**: Configurable API rate limiting for security
- **Input Sanitization**: MongoDB injection prevention
- **Image Validation**: Secure file upload with MIME type validation
- **Responsive UI**: Modern React interface with shadcn-ui components
- **State Management**: Redux Toolkit with RTK Query for API caching
- **Persistent State**: Redux-persist for auth and cart data
- **RESTful API**: Well-structured Laravel API with comprehensive validation

## 🏗️ Architecture

### Monorepo Structure
```
ecommerce-stock-management/
├── client/                     # Frontend application
│   └── shop-sync-react/       # React + TypeScript SPA
│       ├── src/
│       │   ├── store/         # Redux store configuration
│       │   ├── components/    # Reusable UI components
│       │   ├── pages/         # Route pages
│       │   └── main.tsx       # Application entry point
│       └── package.json
├── server/                     # Backend application
│   └── ecommerce-stock-management/  # Laravel API
│       ├── app/
│       │   ├── Http/Controllers/Api/  # API controllers
│       │   ├── Models/        # MongoDB Eloquent models
│       │   ├── Middleware/    # Custom middleware
│       │   └── Services/      # Business logic services
│       ├── routes/api.php     # API routes
│       ├── database/seeders/  # Database seeders
│       └── composer.json
├── Docs/                       # Project documentation
└── README.md                   # This file
```

### Technology Stack

#### Backend
- **Framework**: Laravel 12.0 (PHP 8.2+)
- **Database**: MongoDB 5.5+ (via mongodb/laravel-mongodb)
- **Authentication**: JWT (tymon/jwt-auth)
- **Storage**: AWS S3 / Local filesystem
- **Payment**: Stripe, PayPal
- **Cache**: Redis (optional)
- **File Exports**: Maatwebsite Excel

#### Frontend
- **Framework**: React 18.3 with TypeScript
- **State Management**: Redux Toolkit + RTK Query
- **UI Library**: shadcn-ui + Radix UI
- **Styling**: Tailwind CSS
- **Routing**: React Router v6
- **Build Tool**: Vite
- **Forms**: React Hook Form + Zod validation

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **Node.js**: 18.x or higher ([Download](https://nodejs.org/))
- **npm**: 9.x or higher (comes with Node.js)
- **PHP**: 8.2 or higher ([Download](https://www.php.net/downloads))
- **Composer**: 2.x ([Download](https://getcomposer.org/))
- **MongoDB**: 5.0 or higher ([Download](https://www.mongodb.com/try/download/community)) OR MongoDB Atlas account
- **Redis** (optional): For caching and session storage
- **Docker** (optional): For containerized deployment

### PHP Extensions Required
- `mongodb` extension
- `json`
- `openssl`
- `mbstring`
- `pdo`
- `tokenizer`
- `xml`
- `curl`

Install MongoDB PHP extension:
```bash
# Ubuntu/Debian
sudo pecl install mongodb
echo "extension=mongodb.so" | sudo tee -a /etc/php/8.2/cli/php.ini

# macOS (using Homebrew)
pecl install mongodb
echo "extension=mongodb.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")
```

## 🚀 Quick Start

### Option 1: Manual Setup (Recommended for Development)

#### Backend Setup

1. **Navigate to the backend directory**
   ```bash
   cd server/ecommerce-stock-management
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   
   Update the `.env` file with your configuration:
   ```env
   APP_NAME="E-commerce Stock Management"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   
   # MongoDB Configuration
   DB_CONNECTION=mongodb
   DB_DSN=mongodb://localhost:27017
   DB_DATABASE=ecommerce_stock
   
   # For MongoDB Atlas (Cloud)
   # DB_DSN=mongodb+srv://username:password@cluster.mongodb.net
   
   # JWT Authentication
   JWT_SECRET=  # Will be generated in next step
   JWT_TTL=1440  # Token lifetime in minutes
   
   # File Storage (use 'public' for local, 's3' for AWS)
   FILESYSTEM_DISK=public
   
   # AWS S3 (if using S3 storage)
   AWS_ACCESS_KEY_ID=your_key
   AWS_SECRET_ACCESS_KEY=your_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your_bucket_name
   
   # Redis (optional)
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

4. **Generate application key and JWT secret**
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```

5. **Run database migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed the database with sample data**
   ```bash
   php artisan db:seed
   ```
   
   This creates:
   - Default admin user: `admin@example.com` / `admin123`
   - Sample categories and products
   - Test customer accounts
   - Sample inventory records

7. **Create storage symlink (for local file storage)**
   ```bash
   php artisan storage:link
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```
   
   The API will be available at `http://localhost:8000`

#### Frontend Setup

1. **Navigate to the frontend directory**
   ```bash
   cd client/shop-sync-react
   ```

2. **Install Node.js dependencies**
   ```bash
   npm install
   ```

3. **Start the development server**
   ```bash
   npm run dev
   ```
   
   The application will be available at `http://localhost:5173` (or the port shown in terminal)

### Option 2: Docker Setup

1. **Navigate to the server directory**
   ```bash
   cd server
   ```

2. **Create Docker environment file**
   ```bash
   cp .env.example .env.docker
   ```
   
   Update `.env.docker` with your MongoDB connection and other settings.

3. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```
   
   This will start:
   - Laravel backend on `http://localhost:8000`
   - Redis on `localhost:6379`

4. **Access the container and run migrations**
   ```bash
   docker exec -it esm_laravel bash
   php artisan migrate
   php artisan db:seed
   exit
   ```

5. **Start the frontend separately** (not included in Docker setup)
   ```bash
   cd ../client/shop-sync-react
   npm install
   npm run dev
   ```

## 🔐 Authentication

### Default Credentials

After seeding the database:

**Admin Account**
- Email: `admin@example.com`
- Password: `admin123`

**Important**: Change the admin password immediately in production!

### API Authentication Flow

1. **Login**: POST to `/api/auth/admin/login` or `/api/auth/customer/login`
   ```json
   {
     "email": "admin@example.com",
     "password": "admin123"
   }
   ```

2. **Receive JWT Token**: Store the returned token
   ```json
   {
     "success": true,
     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     "user": { ... }
   }
   ```

3. **Use Token**: Include in Authorization header for protected routes
   ```
   Authorization: Bearer {token}
   ```

4. **Logout**: POST to `/api/auth/logout` (token will be blacklisted)

## 📡 API Endpoints

### Public Endpoints
- `GET /api/health` - Health check
- `GET /api/test` - API status test
- `GET /api/products` - List all products
- `GET /api/products/{id}` - Get product details
- `GET /api/categories` - List all categories

### Authentication Endpoints
- `POST /api/auth/admin/login` - Admin login
- `POST /api/auth/customer/login` - Customer login
- `POST /api/auth/customer/register` - Customer registration
- `GET /api/auth/user` - Get current user (requires JWT)
- `POST /api/auth/logout` - Logout (requires JWT)
- `POST /api/auth/refresh` - Refresh token (requires JWT)
- `PUT /api/auth/password` - Update password (requires JWT)

### Admin Endpoints (require JWT + admin role)
- **Products**
  - `GET /api/admin/products` - List products
  - `POST /api/admin/products` - Create product
  - `GET /api/admin/products/{id}` - Get product
  - `POST /api/admin/products/{id}` - Update product
  - `DELETE /api/admin/products/{id}` - Delete product
  - `POST /api/admin/products/{id}/images` - Upload images
  - `DELETE /api/admin/products/{id}/images` - Delete image

- **Categories**
  - `GET /api/admin/categories` - List categories
  - `POST /api/admin/categories` - Create category
  - `GET /api/admin/categories/{id}` - Get category
  - `PUT /api/admin/categories/{id}` - Update category
  - `DELETE /api/admin/categories/{id}` - Delete category

- **Orders**
  - `GET /api/admin/orders` - List all orders
  - `GET /api/admin/orders/{id}` - Get order details
  - `PUT /api/admin/orders/{id}` - Update order status
  - `DELETE /api/admin/orders/{id}` - Delete order

- **Inventory**
  - `GET /api/admin/inventory/stock-levels` - Get stock levels
  - `GET /api/admin/inventory/low-stock` - Get low stock items
  - `PUT /api/admin/inventory/{id}` - Update stock

- **Customers**
  - `GET /api/admin/customers` - List customers
  - `GET /api/admin/customers/{id}` - Get customer
  - `PUT /api/admin/customers/{id}` - Update customer
  - `DELETE /api/admin/customers/{id}` - Delete customer

### Customer Endpoints (require JWT)
- `GET /api/customer/orders` - List customer's orders
- `POST /api/customer/orders` - Create order
- `GET /api/customer/orders/{id}` - Get order details
- `GET /api/customer/profile` - Get profile
- `PUT /api/customer/profile` - Update profile

## 🧪 Testing

### Backend Testing

```bash
cd server/ecommerce-stock-management

# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage
```

### Frontend Testing

Frontend test infrastructure is planned but not yet implemented.

## 🛠️ Development

### Backend Development

**Code Style**
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

**IDE Helper (for better IDE autocomplete)**
```bash
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta
```

### Frontend Development

**Build Commands**
```bash
# Development build
npm run dev

# Production build
npm run build

# Preview production build
npm run preview

# Lint code
npm run lint
```

**Project Structure**
- `src/store/` - Redux store, slices, and RTK Query APIs
- `src/components/` - Reusable React components
- `src/pages/` - Page components for routes
- `src/lib/` - Utility functions

## 🚢 Deployment

### Backend Deployment (AWS EC2/Elastic Beanstalk)

1. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   Update with production settings:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure MongoDB connection
   - Set JWT secret
   - Configure AWS S3 credentials

2. **Install dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Optimize for production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Set permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### Frontend Deployment (S3 + CloudFront or Vercel)

1. **Build for production**
   ```bash
   cd client/shop-sync-react
   npm run build
   ```

2. **Deploy to S3**
   ```bash
   aws s3 sync dist/ s3://your-bucket-name --delete
   ```

3. **Invalidate CloudFront cache**
   ```bash
   aws cloudfront create-invalidation --distribution-id YOUR_DIST_ID --paths "/*"
   ```

### Docker Deployment

See `server/docker-compose.yml` for container configuration. Update environment variables in `.env.docker` before deploying.

## 🔒 Security

### Security Features
- **JWT Token Blacklisting**: Logged out tokens are blacklisted
- **Input Sanitization**: All user input is sanitized to prevent MongoDB injection
- **File Upload Validation**: Strict MIME type validation (10MB limit, 5 images max)
- **Rate Limiting**: API rate limiting on authentication endpoints
- **CORS Configuration**: Configurable CORS for production
- **Password Hashing**: Bcrypt hashing with configurable rounds

### Security Best Practices
1. Change default admin credentials immediately
2. Use strong JWT secrets in production
3. Enable HTTPS in production
4. Configure proper CORS settings
5. Keep dependencies updated
6. Use environment variables for sensitive data
7. Regular security audits

### Reporting Security Vulnerabilities
Please report security vulnerabilities to the repository maintainers privately. See `server/SECURITY.md` for details.

## 🐛 Troubleshooting

### Common Issues

**MongoDB Connection Error**
```
Solution: Verify MongoDB is running and connection string is correct in .env
Check: DB_DSN and DB_DATABASE values
```

**JWT Secret Not Set**
```
Solution: Run `php artisan jwt:secret`
```

**Storage Link Not Working**
```
Solution: Run `php artisan storage:link`
Verify: storage/app/public is symlinked to public/storage
```

**Frontend API Connection Error**
```
Solution: Verify backend is running on correct port
Check: API base URL in src/store/api/*Api.ts matches backend URL
```

**Port Already in Use**
```bash
# Backend (change port)
php artisan serve --port=8001

# Frontend (automatically uses different port)
npm run dev
```

**MongoDB Extension Not Installed**
```
Solution: Install MongoDB PHP extension (see Prerequisites section)
Verify: php -m | grep mongodb
```

**Redis Connection Error**
```
Solution: Either install Redis or change .env to not use Redis:
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Debug Mode

**Backend Debug**
```bash
# Enable debug mode in .env
APP_DEBUG=true
LOG_LEVEL=debug

# View logs
tail -f storage/logs/laravel.log
```

**Frontend Debug**
```bash
# Use Redux DevTools browser extension
# Console logs are enabled in development mode
```

## 📚 Additional Documentation

- **API Documentation**: Check `Docs/` folder for detailed API documentation
- **Laravel Documentation**: https://laravel.com/docs
- **MongoDB Laravel**: https://www.mongodb.com/docs/drivers/php/laravel-mongodb/
- **React Documentation**: https://react.dev
- **Redux Toolkit**: https://redux-toolkit.js.org

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines
- Follow existing code style and patterns
- Write tests for new features
- Update documentation as needed
- Use meaningful commit messages
- Keep changes focused and atomic

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👥 Authors

- **Innovior Developers** - [GitHub](https://github.com/Innovior-Developers)

## 🙏 Acknowledgments

- Laravel Framework
- React and Redux Toolkit teams
- MongoDB PHP Library
- shadcn-ui for UI components
- All open-source contributors

## 📞 Support

For support and questions:
- Create an issue in the GitHub repository
- Check existing documentation in the `Docs/` folder
- Review troubleshooting section above

## 🗺️ Roadmap

- [ ] Complete social authentication (Google, Facebook, GitHub)
- [ ] Add comprehensive frontend test suite
- [ ] Implement real-time notifications with WebSockets
- [ ] Add advanced analytics dashboard
- [ ] Multi-language support (i18n)
- [ ] Advanced search with Elasticsearch
- [ ] Mobile app version

---

**Built with ❤️ by Innovior Developers**
