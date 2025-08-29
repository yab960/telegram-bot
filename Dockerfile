# Use the official PHP CLI image
FROM php:8.2-cli

# Install PostgreSQL PDO extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /app

# Copy app code
COPY . .

# Expose the port Render will use
EXPOSE 10000

# Start the built-in PHP server
CMD ["php", "-S", "0.0.0.0:10000", "router.php"]
