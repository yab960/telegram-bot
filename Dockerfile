FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Copy your PHP files
COPY . .

# Expose port (Render uses $PORT)
EXPOSE 10000

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "get_number.php"]
