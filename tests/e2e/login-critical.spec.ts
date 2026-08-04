import { test, expect } from '@playwright/test';

// Page Object for Login Page
class LoginPage {
  constructor(page) {
    this.page = page;
    this.emailInput = page.getByLabel('Alamat Email');
    this.passwordInput = page.getByLabel('Kata Sandi');
    this.loginButton = page.getByRole('button', { name: 'Masuk' });
    this.errorMessage = page.getByRole('alert');
    this.pageTitle = page.getByRole('heading', { name: 'Selamat Datang' });
  }

  async goto() {
    await this.page.goto('/login');
    await this.pageTitle.waitFor({ state: 'visible' });
  }

  async login(email, password) {
    await this.emailInput.fill(email);
    await this.passwordInput.fill(password);
    await this.loginButton.click();
  }

  async getErrorMessage() {
    return this.errorMessage.textContent();
  }
}

test.describe('Login - Critical Priority Test', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.goto();
  });

  test('successful login with valid credentials', async ({ page }) => {
    await loginPage.login('fikhahldiansyah28@gmail.com', 'ChangeMe!2026');
    await expect(page).not.toHaveURL(/.*\/login/);
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    await loginPage.login('wrong@example.com', 'wrongpassword');
    await expect(loginPage.errorMessage).toBeVisible();
    await expect(loginPage.errorMessage).toContainText('credentials');
  });
});
