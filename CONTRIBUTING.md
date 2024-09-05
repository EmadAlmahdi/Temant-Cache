
# Contributing to Temant Cache

We welcome contributions! To help ensure the project remains maintainable and consistent, please read the following guidelines before contributing.

## How to Contribute

1. **Fork the repository**  
   Start by forking the repository and creating a branch for your contribution.

2. **Clone the repository**  
   ```bash
   git clone https://github.com/EmadAlmahdi/Temant-Cache

/temant-cache.git
   cd temant-cache
   ```

3. **Install dependencies**  
   Run the following command to install the dependencies:
   ```bash
   composer install
   ```

4. **Running Tests**  
   Run the following command to ensure your changes pass the tests:
   ```bash
   composer test
   ```

5. **Static Analysis**  
   Use PHPStan to ensure your code follows static analysis guidelines:
   ```bash
   composer phpstan
   ```

6. **Create a Pull Request**  
   After making your changes, push your branch to your fork, and create a pull request. Ensure your commit messages follow good practices.

## Code Guidelines

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
- Add tests for any new functionality.
- Ensure that the code coverage does not decrease.

Thank you for your contributions!
