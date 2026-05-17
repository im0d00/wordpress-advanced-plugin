<?php
class HeadingTest extends WP_UnitTestCase {

    private Heading $element;

    public function setUp(): void {
        parent::setUp();
        $this->element = new Heading();
    }

    public function test_get_name(): void {
        $this->assertEquals('heading', $this->element->get_name());
    }

    public function test_render_correct_tag(): void {
        $html = $this->element->render_element(['tag' => 'h1', 'text' => 'Hello']);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function test_xss_in_text_is_stripped(): void {
        $html = $this->element->render_element([
            'tag'  => 'h2',
            'text' => '<script>alert("xss")</script>Safe text',
        ]);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Safe text', $html);
    }

    public function test_link_is_escaped(): void {
        $html = $this->element->render_element([
            'tag'  => 'h2',
            'text' => 'Click me',
            'link' => ['url' => 'javascript:alert(1)'],
        ]);
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
