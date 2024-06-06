# PayFast API Integration Assessment - Koketso Mabuela

## Setup Instructions

### Prerequisites

- Docker
- Docker Compose
- PHP 8.1
- Composer
- Nginx

1. Clone the repository
2. Run `composer install`
3. Create a `.env` file and update with your database and PayFast credentials
4. Run `php artisan migrate`
5. Run `php artisan serve`

## API Endpoints

### Create Payment
- Endpoint: `POST /api/payments`
- Parameters: `amount`, `currency`, `customer_email`
- Response: `{ "redirect_url": "https://www.payfast.co.za/eng/process?... }`

### IPN Handler
- Endpoint: `POST /api/payments/ipn`
- Parameters: `pf_payment_id`, `amount_gross`
- Response: `{ "status": "success" }`

## Testing
- Run `php artisan test`

## Swagger Documentation

The Swagger YAML document can be accessed at: `http://localhost/swagger.yaml`

## Design Decisions
- Used Laravel for robust and easy API development.
- JWT for authentication for secure API access.
- Mocked PayFast IPN handler for simplicity.
- Dockerized the application for easy setup and consistent development environment.
