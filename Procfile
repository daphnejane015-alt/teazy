web: php artisan serve --host=0.0.0.0 --port=$PORT
worker: while true; do php artisan schedule:run --verbose --no-interaction & sleep 60; done
