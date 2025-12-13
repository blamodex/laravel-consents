<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Validators\ConsentValidator;
use Blamodex\Consent\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

class ConsentValidatorTest extends TestCase
{
    private ConsentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ConsentValidator();
    }

    // ==========================================
    // Valid Payloads
    // ==========================================

    #[Test]
    public function it_passes_valid_consent_payload(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'I agree to receive marketing emails.',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
        $this->assertTrue($this->validator->validate($data));
    }

    #[Test]
    public function it_passes_multiple_consent_records(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Email consent',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
                [
                    'text' => 'SMS consent',
                    'source' => 'mobile-app',
                    'slug' => 'sms-marketing',
                    'state' => 'revoked',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
    }

    #[Test]
    public function it_passes_when_text_is_null(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => null,
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
    }

    #[Test]
    public function it_passes_when_text_is_missing(): void
    {
        $data = [
            'consents' => [
                [
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
    }

    #[Test]
    public function it_passes_with_revoked_state(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Revoked consent',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'revoked',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
    }

    #[Test]
    public function it_fails_with_empty_consents_array(): void
    {
        $data = [
            'consents' => [],
        ];

        // Empty consents array fails validation because wildcard rules require items
        // This is acceptable - callers should check if they have consents before validating
        $this->assertFalse($this->validator->passes($data));
    }

    // ==========================================
    // Invalid Payloads - Missing Required Fields
    // ==========================================

    #[Test]
    public function it_fails_when_consents_key_is_missing(): void
    {
        $data = [];

        $this->assertFalse($this->validator->passes($data));
        
        $this->expectException(ValidationException::class);
        $this->validator->validate($data);
    }

    #[Test]
    public function it_fails_when_consents_is_not_an_array(): void
    {
        $data = [
            'consents' => 'not-an-array',
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents', $errors);
    }

    #[Test]
    public function it_fails_when_source_is_missing(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.source', $errors);
    }

    #[Test]
    public function it_fails_when_slug_is_missing(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 'web-form',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.slug', $errors);
    }

    #[Test]
    public function it_fails_when_state_is_missing(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.state', $errors);
    }

    // ==========================================
    // Invalid Payloads - Invalid Data Types
    // ==========================================

    #[Test]
    public function it_fails_when_text_is_not_a_string(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 12345,
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.text', $errors);
    }

    #[Test]
    public function it_fails_when_source_is_not_a_string(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 123,
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.source', $errors);
    }

    #[Test]
    public function it_fails_when_slug_is_not_a_string(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 'web-form',
                    'slug' => ['not-a-string'],
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.slug', $errors);
    }

    #[Test]
    public function it_fails_when_state_is_not_a_string(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => true,
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.state', $errors);
    }

    // ==========================================
    // Invalid Payloads - Invalid Values
    // ==========================================

    #[Test]
    public function it_fails_when_state_is_invalid(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'invalid-state',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.state', $errors);
        $this->assertStringContainsString('consented', $errors['consents.0.state'][0]);
        $this->assertStringContainsString('revoked', $errors['consents.0.state'][0]);
    }

    #[Test]
    public function it_fails_when_text_exceeds_max_length(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => str_repeat('a', 1001),
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.text', $errors);
    }

    #[Test]
    public function it_fails_when_source_exceeds_max_length(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => str_repeat('a', 256),
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.source', $errors);
    }

    #[Test]
    public function it_fails_when_slug_exceeds_max_length(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => 'web-form',
                    'slug' => str_repeat('a', 256),
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.slug', $errors);
    }

    // ==========================================
    // Multiple Records with Mixed Validity
    // ==========================================

    #[Test]
    public function it_validates_each_consent_record_independently(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Valid consent',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
                [
                    'text' => 'Invalid consent',
                    'source' => 'web-form',
                    'slug' => 'sms-marketing',
                    'state' => 'invalid-state',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayNotHasKey('consents.0.state', $errors);
        $this->assertArrayHasKey('consents.1.state', $errors);
    }

    #[Test]
    public function it_returns_multiple_errors_for_invalid_record(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 12345,
                    // Missing source
                    // Missing slug
                    'state' => 'invalid-state',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        $this->assertArrayHasKey('consents.0.text', $errors);
        $this->assertArrayHasKey('consents.0.source', $errors);
        $this->assertArrayHasKey('consents.0.slug', $errors);
        $this->assertArrayHasKey('consents.0.state', $errors);
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    #[Test]
    public function it_passes_with_long_but_valid_text(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => str_repeat('a', 1000),
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
    }

    #[Test]
    public function it_passes_with_max_length_source_and_slug(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => 'Some text',
                    'source' => str_repeat('a', 255),
                    'slug' => str_repeat('b', 255),
                    'state' => 'consented',
                ],
            ],
        ];

        $this->assertTrue($this->validator->passes($data));
    }

    #[Test]
    public function it_handles_empty_strings_for_required_fields(): void
    {
        $data = [
            'consents' => [
                [
                    'text' => '',
                    'source' => '',
                    'slug' => '',
                    'state' => '',
                ],
            ],
        ];

        $this->assertFalse($this->validator->passes($data));
        
        $errors = $this->validator->errors($data);
        // Empty strings should fail the 'in' rule for state
        $this->assertArrayHasKey('consents.0.state', $errors);
    }
}
