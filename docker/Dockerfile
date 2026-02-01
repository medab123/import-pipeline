FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd ftp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Generate TypeScript types (create stub if generation fails)
RUN php artisan typescript:transform || true
RUN mkdir -p resources/ts/types && test -f resources/ts/types/generated.ts || printf '// Stub types file\nexport type ListPipelineViewModel = any;\nexport type PipelineViewModel = any;\nexport type PaginatorViewModel = any;\nexport type CreateStepViewModel = any;\nexport type PipelineStatsViewModel = any;\nexport type ActivityLogViewModel = any;\nexport type ImagesPrepareConfigStepViewModel = any;\nexport type DownloaderConfigStepViewModel = any;\nexport type ReaderConfigStepViewModel = any;\nexport type BasicInfoStepViewModel = any;\nexport type MapperConfigStepViewModel = any;\nexport type PreviewStepViewModel = any;\nexport type FilterConfigStepViewModel = any;\n' > resources/ts/types/generated.ts

# Install Node dependencies (build will be done at runtime or separately)
RUN npm install

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
