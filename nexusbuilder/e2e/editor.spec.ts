import { test, expect } from '@playwright/test';

test.describe('NexusBuilder Editor', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.goto('/wp-admin/post.php?post=1&action=edit');
    await page.click('[data-nexusbuilder-edit]');
    await page.waitForSelector('.nexus-canvas', { timeout: 10_000 });
  });

  test('drag a heading element onto canvas', async ({ page }) => {
    const headingWidget = page.locator('[data-element="heading"]').first();
    const canvas        = page.locator('.nexus-canvas');

    await headingWidget.dragTo(canvas);
    await expect(page.locator('.nexus-el[data-type="heading"]')).toBeVisible();
  });

  test('clicking element opens style controls in right panel', async ({ page }) => {
    await page.locator('.nexus-el').first().click();
    await expect(page.locator('.nexus-panel-right')).toBeVisible();
    await expect(page.locator('[data-control="text"]')).toBeVisible();
  });

  test('changing font size updates canvas in real time', async ({ page }) => {
    await page.locator('.nexus-el[data-type="heading"]').first().click();
    await page.click('[data-tab="style"]');

    const sizeInput = page.locator('[data-control="typography_size"] input');
    await sizeInput.clear();
    await sizeInput.fill('72');
    await sizeInput.press('Enter');

    const heading = page.locator('.nexus-el[data-type="heading"] .nexus-heading').first();
    const fontSize = await heading.evaluate(el => window.getComputedStyle(el).fontSize);
    expect(fontSize).toBe('72px');
  });

  test('undo reverts last change', async ({ page }) => {
    const heading = page.locator('.nexus-el[data-type="heading"]').first();
    await heading.click();
    const textInput = page.locator('[data-control="text"] textarea');
    await textInput.fill('Changed Text');

    await page.keyboard.press('Control+z');
    await expect(textInput).not.toHaveValue('Changed Text');
  });

  test('save persists to database', async ({ page }) => {
    await page.keyboard.press('Control+s');
    await expect(page.locator('.nexus-save-indicator')).toHaveText('Saved');
  });
});
