<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\Validation;
use Cake\Validation\ValidationRule;
use Cake\Validation\ValidationSet;
use Cake\Validation\Validator;

/**
 * Tests Validator class
 */
class ValidatorTest extends TestCase
{

    /**
     * tests getRequiredMessage
     *
     * @return void
     */
    public function testGetRequiredMessage()
    {
        $validator = new Validator();
        $this->assertNull($validator->getRequiredMessage('field'));

        $validator = new Validator();
        $validator->requirePresence('field');
        $this->assertSame('This field is required', $validator->getRequiredMessage('field'));

        $validator = new Validator();
        $validator->requirePresence('field', true, 'Custom message');
        $this->assertSame('Custom message', $validator->getRequiredMessage('field'));
    }

    /**
     * tests getNotEmptyMessage
     *
     * @return void
     */
    public function testGetNotEmptyMessage()
    {
        $validator = new Validator();
        $this->assertNull($validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->requirePresence('field');
        $this->assertSame('This field cannot be left empty', $validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->notEmpty('field', 'Custom message');
        $this->assertSame('Custom message', $validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->notBlank('field', 'Cannot be blank');
        $this->assertSame('Cannot be blank', $validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->notEmpty('field', 'Cannot be empty');
        $validator->notBlank('field', 'Cannot be blank');
        $this->assertSame('Cannot be blank', $validator->getNotEmptyMessage('field'));
    }

    /**
     * Testing you can dynamically add rules to a field
     *
     * @return void
     */
    public function testAddingRulesToField()
    {
        $validator = new Validator();
        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $set = $validator->field('title');
        $this->assertInstanceOf('Cake\Validation\ValidationSet', $set);
        $this->assertCount(1, $set);

        $validator->add('title', 'another', ['rule' => 'alphanumeric']);
        $this->assertCount(2, $set);

        $validator->add('body', 'another', ['rule' => 'crazy']);
        $this->assertCount(1, $validator->field('body'));
        $this->assertCount(2, $validator);

        $validator->add('email', 'notBlank');
        $result = $validator->field('email')->rule('notBlank')->get('rule');
        $this->assertEquals('notBlank', $result);

        $rule = new ValidationRule();
        $validator->add('field', 'myrule', $rule);
        $result = $validator->field('field')->rule('myrule');
        $this->assertSame($rule, $result);
    }

    /**
     * Testing addNested field rules
     *
     * @return void
     */
    public function testAddNestedSingle()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'not-blank', ['rule' => 'notBlank']);
        $this->assertSame($validator, $validator->addNested('user', $inner));

        $this->assertCount(1, $validator->field('user'));
    }

    /**
     * Testing addNested connects providers
     *
     * @return void
     */
    public function testAddNestedSingleProviders()
    {
        $validator = new Validator();
        $validator->setProvider('test', $this);

        $inner = new Validator();
        $inner->add('username', 'not-blank', ['rule' => function () use ($inner, $validator) {
            $this->assertSame($validator->providers(), $inner->providers(), 'Providers should match');

            return false;
        }]);
        $validator->addNested('user', $inner);

        $result = $validator->errors(['user' => ['username' => 'example']]);
        $this->assertNotEmpty($result, 'Validation should fail');
    }

    /**
     * Testing addNested with extra `$message` and `$when` params
     *
     * @return void
     */
    public function testAddNestedWithExtra()
    {
        $inner = new Validator();
        $inner->requirePresence('username');

        $validator = new Validator();
        $validator->addNested('user', $inner, 'errors found', 'create');

        $this->assertCount(1, $validator->field('user'));

        $rule = $validator->field('user')->rule(Validator::NESTED);
        $this->assertSame('create', $rule->get('on'));

        $errors = $validator->errors(['user' => 'string']);
        $this->assertArrayHasKey('user', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['user']);
        $this->assertSame('errors found', $errors['user'][Validator::NESTED]);

        $errors = $validator->errors(['user' => ['key' => 'value']]);
        $this->assertArrayHasKey('user', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['user']);

        $this->assertEmpty($validator->errors(['user' => ['key' => 'value']], false));
    }

    /**
     * Testing addNestedMany field rules
     *
     * @return void
     */
    public function testAddNestedMany()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('comment', 'not-blank', ['rule' => 'notBlank']);
        $this->assertSame($validator, $validator->addNestedMany('comments', $inner));

        $this->assertCount(1, $validator->field('comments'));
    }

    /**
     * Testing addNestedMany connects providers
     *
     * @return void
     */
    public function testAddNestedManyProviders()
    {
        $validator = new Validator();
        $validator->setProvider('test', $this);

        $inner = new Validator();
        $inner->add('comment', 'not-blank', ['rule' => function () use ($inner, $validator) {
            $this->assertSame($validator->providers(), $inner->providers(), 'Providers should match');

            return false;
        }]);
        $validator->addNestedMany('comments', $inner);

        $result = $validator->errors(['comments' => [['comment' => 'example']]]);
        $this->assertNotEmpty($result, 'Validation should fail');
    }

    /**
     * Testing addNestedMany with extra `$message` and `$when` params
     *
     * @return void
     */
    public function testAddNestedManyWithExtra()
    {
        $inner = new Validator();
        $inner->requirePresence('body');

        $validator = new Validator();
        $validator->addNestedMany('comments', $inner, 'errors found', 'create');

        $this->assertCount(1, $validator->field('comments'));

        $rule = $validator->field('comments')->rule(Validator::NESTED);
        $this->assertSame('create', $rule->get('on'));

        $errors = $validator->errors(['comments' => 'string']);
        $this->assertArrayHasKey('comments', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['comments']);
        $this->assertSame('errors found', $errors['comments'][Validator::NESTED]);

        $errors = $validator->errors(['comments' => ['string']]);
        $this->assertArrayHasKey('comments', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['comments']);
        $this->assertSame('errors found', $errors['comments'][Validator::NESTED]);

        $errors = $validator->errors(['comments' => [['body' => null]]]);
        $this->assertArrayHasKey('comments', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['comments']);

        $this->assertEmpty($validator->errors(['comments' => [['body' => null]]], false));
    }

    /**
     * Tests that calling field will create a default validation set for it
     *
     * @return void
     */
    public function testFieldDefault()
    {
        $validator = new Validator();
        $this->assertFalse($validator->hasField('foo'));

        $field = $validator->field('foo');
        $this->assertInstanceOf('Cake\Validation\ValidationSet', $field);
        $this->assertCount(0, $field);
        $this->assertTrue($validator->hasField('foo'));
    }

    /**
     * Tests that field method can be used as a setter
     *
     * @return void
     */
    public function testFieldSetter()
    {
        $validator = new Validator();
        $validationSet = new ValidationSet();
        $validator->field('thing', $validationSet);
        $this->assertSame($validationSet, $validator->field('thing'));
    }

    /**
     * Tests the remove method
     *
     * @return void
     */
    public function testRemove()
    {
        $validator = new Validator();
        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $validator->add('title', 'foo', ['rule' => 'bar']);
        $this->assertCount(2, $validator->field('title'));
        $validator->remove('title');
        $this->assertCount(0, $validator->field('title'));
        $validator->remove('title');

        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $validator->add('title', 'foo', ['rule' => 'bar']);
        $this->assertCount(2, $validator->field('title'));
        $validator->remove('title', 'foo');
        $this->assertCount(1, $validator->field('title'));
        $this->assertNull($validator->field('title')->rule('foo'));
    }

    /**
     * Tests the requirePresence method
     *
     * @return void
     */
    public function testRequirePresence()
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->requirePresence('title'));
        $this->assertTrue($validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', false);
        $this->assertFalse($validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', 'create');
        $this->assertEquals('create', $validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', 'update');
        $this->assertEquals('update', $validator->field('title')->isPresenceRequired());
    }

    /**
     * Tests the requirePresence method
     *
     * @return void
     */
    public function testRequirePresenceAsArray()
    {
        $validator = new Validator();
        $validator->requirePresence(['title', 'created']);
        $this->assertTrue($validator->field('title')->isPresenceRequired());
        $this->assertTrue($validator->field('created')->isPresenceRequired());

        $validator->requirePresence([
            'title' => [
                'mode' => false,
            ],
            'content' => [
                'mode' => 'update',
            ],
            'subject',
        ], true);
        $this->assertFalse($validator->field('title')->isPresenceRequired());
        $this->assertEquals('update', $validator->field('content')->isPresenceRequired());
        $this->assertTrue($validator->field('subject')->isPresenceRequired());
    }

    /**
     * Tests the requirePresence failure case
     *
     * @return void
     */
    public function testRequirePresenceAsArrayFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $validator = new Validator();
        $validator->requirePresence(['title' => 'derp', 'created' => false]);
    }

    /**
     * Tests the requirePresence method when passing a callback
     *
     * @return void
     */
    public function testRequirePresenceCallback()
    {
        $validator = new Validator();
        $require = true;
        $validator->requirePresence('title', function ($context) use (&$require) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertEquals('title', $context['field']);
            $this->assertTrue($context['newRecord']);

            return $require;
        });
        $this->assertTrue($validator->isPresenceRequired('title', true));

        $require = false;
        $this->assertFalse($validator->isPresenceRequired('title', true));
    }

    /**
     * Tests the isPresenceRequired method
     *
     * @return void
     */
    public function testIsPresenceRequired()
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->requirePresence('title'));
        $this->assertTrue($validator->isPresenceRequired('title', true));
        $this->assertTrue($validator->isPresenceRequired('title', false));

        $validator->requirePresence('title', false);
        $this->assertFalse($validator->isPresenceRequired('title', true));
        $this->assertFalse($validator->isPresenceRequired('title', false));

        $validator->requirePresence('title', 'create');
        $this->assertTrue($validator->isPresenceRequired('title', true));
        $this->assertFalse($validator->isPresenceRequired('title', false));

        $validator->requirePresence('title', 'update');
        $this->assertTrue($validator->isPresenceRequired('title', false));
        $this->assertFalse($validator->isPresenceRequired('title', true));
    }

    /**
     * Tests errors generated when a field presence is required
     *
     * @return void
     */
    public function testErrorsWithPresenceRequired()
    {
        $validator = new Validator();
        $validator->requirePresence('title');
        $errors = $validator->errors(['foo' => 'something']);
        $expected = ['title' => ['_required' => 'This field is required']];
        $this->assertEquals($expected, $errors);

        $this->assertEmpty($validator->errors(['title' => 'bar']));

        $validator->requirePresence('title', false);
        $this->assertEmpty($validator->errors(['foo' => 'bar']));
    }

    /**
     * Test that validation on a certain condition generate errors
     *
     * @return void
     */
    public function testErrorsWithPresenceRequiredOnCreate()
    {
        $validator = new Validator();
        $validator->requirePresence('id', 'update');
        $validator->allowEmpty('id', 'create');
        $validator->requirePresence('title');

        $data = [
            'title' => 'Example title',
        ];

        $expected = [];
        $result = $validator->errors($data);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that errors() can work with nested data.
     *
     * @return void
     */
    public function testErrorsWithNestedFields()
    {
        $validator = new Validator();
        $user = new Validator();
        $user->add('username', 'letter', ['rule' => 'alphanumeric']);

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);

        $validator->addNested('user', $user);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'user' => [
                'username' => 'is wrong',
            ],
            'comments' => [
                ['comment' => 'is wrong'],
            ],
        ];
        $errors = $validator->errors($data);
        $expected = [
            'user' => [
                'username' => ['letter' => 'The provided value is invalid'],
            ],
            'comments' => [
                0 => ['comment' => ['letter' => 'The provided value is invalid']],
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     *
     * @return void
     */
    public function testErrorsWithNestedSingleInvalidType()
    {
        $validator = new Validator();

        $user = new Validator();
        $user->add('user', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNested('user', $user);

        $data = [
            'user' => 'a string',
        ];
        $errors = $validator->errors($data);
        $expected = [
            'user' => ['_nested' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     *
     * @return void
     */
    public function testErrorsWithNestedManyInvalidType()
    {
        $validator = new Validator();

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'comments' => 'a string',
        ];
        $errors = $validator->errors($data);
        $expected = [
            'comments' => ['_nested' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     *
     * @return void
     */
    public function testErrorsWithNestedManySomeInvalid()
    {
        $validator = new Validator();

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'comments' => [
                'a string',
                ['comment' => 'letters'],
                ['comment' => 'more invalid'],
            ],
        ];
        $errors = $validator->errors($data);
        $expected = [
            'comments' => [
                '_nested' => 'The provided value is invalid',
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field presence is required
     *
     * @return void
     */
    public function testCustomErrorsWithPresenceRequired()
    {
        $validator = new Validator();
        $validator->requirePresence('title', true, 'Custom message');
        $errors = $validator->errors(['foo' => 'something']);
        $expected = ['title' => ['_required' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field presence is required
     *
     * @return void
     */
    public function testCustomErrorsWithPresenceRequiredAsArray()
    {
        $validator = new Validator();
        $validator->requirePresence(['title', 'content'], true, 'Custom message');
        $errors = $validator->errors(['foo' => 'something']);
        $expected = [
            'title' => ['_required' => 'Custom message'],
            'content' => ['_required' => 'Custom message'],
        ];
        $this->assertEquals($expected, $errors);

        $validator->requirePresence([
            'title' => [
                'message' => 'Test message',
            ],
            'content',
        ], true, 'Custom message');
        $errors = $validator->errors(['foo' => 'something']);
        $expected = [
            'title' => ['_required' => 'Test message'],
            'content' => ['_required' => 'Custom message'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the allowEmpty method
     *
     * @return void
     */
    public function testAllowEmpty()
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->allowEmpty('title'));
        $this->assertTrue($validator->field('title')->isEmptyAllowed());

        $validator->allowEmpty('title', 'create');
        $this->assertEquals('create', $validator->field('title')->isEmptyAllowed());

        $validator->allowEmpty('title', 'update');
        $this->assertEquals('update', $validator->field('title')->isEmptyAllowed());
    }

    /**
     * Tests the allowEmpty method with date/time fields.
     *
     * @return void
     */
    public function testAllowEmptyWithDateTimeFields()
    {
        $validator = new Validator();
        $validator->allowEmpty('created')
            ->add('created', 'date', ['rule' => 'date']);

        $data = [
            'created' => [
                'year' => '',
                'month' => '',
                'day' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty date');

        $data = [
            'created' => [
                'year' => '',
                'month' => '',
                'day' => '',
                'hour' => '',
                'minute' => '',
                'second' => '',
                'meridian' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty datetime');

        $data = [
            'created' => [
                'hour' => '',
                'minute' => '',
                'meridian' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty time');
    }

    /**
     * Tests the allowEmpty method with file fields.
     *
     * @return void
     */
    public function testAllowEmptyWithFileFields()
    {
        $validator = new Validator();
        $validator->allowEmpty('picture')
            ->add('picture', 'file', ['rule' => 'uploadedFile']);

        $data = [
            'picture' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty date');

        $data = [
            'picture' => [
                'name' => 'fake.png',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_OK,
            ],
        ];
        $result = $validator->errors($data);
        $this->assertNotEmpty($result, 'Invalid file should be caught still.');
    }

    /**
     * Tests the allowEmpty as array method
     *
     * @return void
     */
    public function testAllowEmptyAsArray()
    {
        $validator = new Validator();

        $validator->allowEmpty([
            'title',
            'subject',
            'posted_at' => [
                'when' => false,
                'message' => 'Post time cannot be empty',
            ],
            'updated_at' => [
                'when' => true,
            ],
            'show_at' => [
                'when' => 'update',
            ],
        ], 'create', 'Cannot be empty');
        $this->assertEquals('create', $validator->field('title')->isEmptyAllowed());
        $this->assertEquals('create', $validator->field('subject')->isEmptyAllowed());
        $this->assertFalse($validator->field('posted_at')->isEmptyAllowed());
        $this->assertTrue($validator->field('updated_at')->isEmptyAllowed());
        $this->assertEquals('update', $validator->field('show_at')->isEmptyAllowed());

        $errors = $validator->errors([
            'title' => '',
            'subject' => null,
            'posted_at' => null,
            'updated_at' => null,
            'show_at' => '',
        ], false);

        $expected = [
            'title' => ['_empty' => 'Cannot be empty'],
            'subject' => ['_empty' => 'Cannot be empty'],
            'posted_at' => ['_empty' => 'Post time cannot be empty'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the allowEmpty failure case
     *
     * @return void
     */
    public function testAllowEmptyAsArrayFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $validator = new Validator();
        $validator->allowEmpty(['title' => 'derp', 'created' => false]);
    }

    /**
     * Tests the allowEmptyString method
     *
     * @return void
     */
    public function testAllowEmptyString()
    {
        $validator = new Validator();
        $validator->allowEmptyString('title')
            ->scalar('title');

        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $data = [
            'title' => '',
        ];
        $this->assertEmpty($validator->errors($data));

        $data = [
            'title' => null,
        ];
        $this->assertEmpty($validator->errors($data));

        $data = [
            'title' => [],
        ];
        $this->assertNotEmpty($validator->errors($data));

        $validator = new Validator();
        $validator->allowEmptyString('title', 'message', 'update');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $data = [
            'title' => null,
        ];
        $expected = [
            'title' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Ensure that allowEmptyString() works with deprecated arguments
     *
     * @return void
     */
    public function testAllowEmptyStringDeprecatedArguments()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyString('title', 'update', 'message');
        });
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $data = [
            'title' => null,
        ];
        $expected = [
            'title' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Same as testAllowEmptyDateUpdateDeprecatedArguments but without message
     *
     * @return void
     */
    public function testAllowEmptyStringDeprecatedArgumentsWithoutMessage()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyString('title', 'update');
        });
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $data = [
            'title' => null,
        ];
        $expected = [
            'title' => ['_empty' => 'This field cannot be left empty'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Tests the notEmptyString method
     *
     * @return void
     */
    public function testNotEmptyString()
    {
        $validator = new Validator();
        $validator->notEmptyString('title', 'not empty');

        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $data = ['title' => '0'];
        $this->assertEmpty($validator->errors($data));

        $data = ['title' => 0];
        $this->assertEmpty($validator->errors($data), 'empty ok on create');
        $this->assertEmpty($validator->errors($data, false), 'empty ok on update');

        $data = ['title' => []];
        $this->assertEmpty($validator->errors($data), 'empty array is no good');

        $expected = [
            'title' => ['_empty' => 'not empty'],
        ];
        $data = ['title' => ''];
        $this->assertSame($expected, $validator->errors($data, true));
    }

    /**
     * Test notEmptyString with explicit create.
     *
     * @return void
     */
    public function testNotEmptyStringCreate()
    {
        $validator = new Validator();
        $validator->notEmptyString('title', 'message', 'create');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $expected = [
            'title' => ['_empty' => 'message'],
        ];
        $data = ['title' => null];
        $this->assertSame($expected, $validator->errors($data, true));

        $data = ['title' => ''];
        $this->assertSame($expected, $validator->errors($data, true));

        $data = ['title' => ''];
        $this->assertEmpty($validator->errors($data, false), 'empty allowed on update');
    }

    /**
     * Test notEmptyString with callback
     *
     * @return void
     */
    public function testNotEmptyStringCallbackWhen()
    {
        $validator = new Validator();
        $validator->notEmptyString('title', 'message', function ($context) {
            if (!isset($context['data']['emptyOk'])) {
                return true;
            }

            return $context['data']['emptyOk'];
        });

        $error = [
            'title' => ['_empty' => 'message'],
        ];
        $data = ['title' => ''];
        $this->assertSame($error, $validator->errors($data));

        $data = ['title' => '', 'emptyOk' => false];
        $this->assertEmpty($validator->errors($data));

        $data = ['title' => '', 'emptyOk' => true];
        $this->assertSame($error, $validator->errors($data));
    }

    /**
     * Tests the allowEmptyArray method
     *
     * @return void
     */
    public function testAllowEmptyArray()
    {
        $validator = new Validator();
        $validator->allowEmptyArray('items')
            ->hasAtMost('items', 3);

        $this->assertTrue($validator->field('items')->isEmptyAllowed());

        $data = [
            'items' => '',
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'items' => null,
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'items' => [],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'items' => [1, 2, 3, 4, 5],
        ];
        $expected = [
            'items' => [
                'hasAtMost' => 'The provided value is invalid',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertSame($expected, $result);
    }

    /**
     * Test allowEmptyArray with update mode
     *
     * @return void
     */
    public function testAllowEmptyArrayUpdate()
    {
        $validator = new Validator();
        $validator->allowEmptyArray('items', 'message', 'update');
        $this->assertFalse($validator->isEmptyAllowed('items', true));
        $this->assertTrue($validator->isEmptyAllowed('items', false));

        $data = [
            'items' => null,
        ];
        $expected = [
            'items' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Test allowEmptyArray with update mode
     *
     * @return void
     */
    public function testAllowEmptyArrayUpdateDeprecatedOrder()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyArray('items', 'update', 'message');
        });
        $this->assertFalse($validator->isEmptyAllowed('items', true));
        $this->assertTrue($validator->isEmptyAllowed('items', false));

        $data = [
            'items' => null,
        ];
        $expected = [
            'items' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Tests the notEmptyArray method
     *
     * @return void
     */
    public function testNotEmptyArray()
    {
        $validator = new Validator();
        $validator->notEmptyArray('items', 'not empty');

        $this->assertFalse($validator->field('items')->isEmptyAllowed());

        $error = [
            'items' => ['_empty' => 'not empty'],
        ];
        $data = ['items' => ''];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['items' => null];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['items' => []];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = [
            'items' => [1],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);
    }

    /**
     * Tests the allowEmptyFile method
     *
     * @return void
     */
    public function testAllowEmptyFile()
    {
        $validator = new Validator();
        $validator->allowEmptyFile('photo')
            ->uploadedFile('photo', []);

        $this->assertTrue($validator->field('photo')->isEmptyAllowed());

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'photo' => null,
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_FORM_SIZE,
            ],
        ];
        $expected = [
            'photo' => [
                'uploadedFile' => 'The provided value is invalid',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertSame($expected, $result);

        $data = [
            'photo' => '',
        ];
        $expected = [
            'photo' => [
                'uploadedFile' => 'The provided value is invalid',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertSame($expected, $result);

        $data = ['photo' => []];
        $result = $validator->errors($data);
        $this->assertSame($expected, $result);

        $validator = new Validator();
        $validator->allowEmptyFile('photo', 'message', 'update');
        $this->assertFalse($validator->isEmptyAllowed('photo', true));
        $this->assertTrue($validator->isEmptyAllowed('photo', false));

        $data = [
            'photo' => null,
        ];
        $expected = [
            'photo' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Test deprecated argument order for allowEmptyFile
     *
     * @return void
     */
    public function testAllowEmptyFileDeprecated()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyFile('photo', 'update', 'message');
        });
        $this->assertFalse($validator->isEmptyAllowed('photo', true));
        $this->assertTrue($validator->isEmptyAllowed('photo', false));

        $data = [
            'photo' => null,
        ];
        $expected = [
            'photo' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Tests the notEmptyFile method
     *
     * @return void
     */
    public function testNotEmptyFile()
    {
        $validator = new Validator();
        $validator->notEmptyFile('photo', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('photo', true));
        $this->assertFalse($validator->isEmptyAllowed('photo', false));

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];
        $error = ['photo' => ['_empty' => 'required field']];
        $this->assertSame($error, $validator->errors($data));

        $data = ['photo' => null];
        $this->assertSame($error, $validator->errors($data));

        // Empty string and empty array don't trigger errors
        // as rejecting them here would mean accepting them in
        // allowEmptyFile() which is not desirable.
        $data = ['photo' => ''];
        $this->assertEmpty($validator->errors($data));

        $data = ['photo' => []];
        $this->assertEmpty($validator->errors($data));

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_FORM_SIZE,
            ],
        ];
        $this->assertEmpty($validator->errors($data));
    }

    /**
     * Test notEmptyFile with update mode.
     *
     * @retrn void
     */
    public function testNotEmptyFileUpdate()
    {
        $validator = new Validator();
        $validator->notEmptyArray('photo', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('photo', true));
        $this->assertFalse($validator->isEmptyAllowed('photo', false));

        $data = ['photo' => null];
        $expected = [
            'photo' => ['_empty' => 'message'],
        ];
        $this->assertEmpty($validator->errors($data, true));
        $this->assertSame($expected, $validator->errors($data, false));
    }

    /**
     * Tests the allowEmptyDate method
     *
     * @return void
     */
    public function testAllowEmptyDate()
    {
        $validator = new Validator();
        $validator->allowEmptyDate('date')
            ->date('date');

        $this->assertTrue($validator->field('date')->isEmptyAllowed());

        $data = [
            'date' => [
                'year' => '',
                'month' => '',
                'day' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'date' => '',
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'date' => null,
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = ['date' => []];
        $result = $validator->errors($data);
        $this->assertEmpty($result);
    }

    /**
     * test allowEmptyDate() with an update condition
     *
     * @return void
     */
    public function testAllowEmptyDateUpdate()
    {
        $validator = new Validator();
        $validator->allowEmptyArray('date', 'be valid', 'update');
        $this->assertFalse($validator->isEmptyAllowed('date', true));
        $this->assertTrue($validator->isEmptyAllowed('date', false));

        $data = [
            'date' => null,
        ];
        $expected = [
            'date' => ['_empty' => 'be valid'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * test allowEmptyDate() with an update condition
     *
     * @return void
     */
    public function testAllowEmptyDateUpdateDeprecatedArguments()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyArray('date', 'update', 'be valid');
        });
        $this->assertFalse($validator->isEmptyAllowed('date', true));
        $this->assertTrue($validator->isEmptyAllowed('date', false));

        $data = [
            'date' => null,
        ];
        $expected = [
            'date' => ['_empty' => 'be valid'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Tests the notEmptyDate method
     *
     * @return void
     */
    public function testNotEmptyDate()
    {
        $validator = new Validator();
        $validator->notEmptyDate('date', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('date', true));
        $this->assertFalse($validator->isEmptyAllowed('date', false));

        $error = ['date' => ['_empty' => 'required field']];
        $data = [
            'date' => [
                'year' => '',
                'month' => '',
                'day' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['date' => ''];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['date' => null];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['date' => []];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = [
            'date' => [
                'year' => 2019,
                'month' => 2,
                'day' => 17,
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);
    }

    /**
     * Test notEmptyDate with update mode
     *
     * @return void
     */
    public function testNotEmptyDateUpdate()
    {
        $validator = new Validator();
        $validator->notEmptyDate('date', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('date', true));
        $this->assertFalse($validator->isEmptyAllowed('date', false));

        $data = ['date' => null];
        $expected = ['date' => ['_empty' => 'message']];
        $this->assertSame($expected, $validator->errors($data, false));
        $this->assertEmpty($validator->errors($data, true));
    }

    /**
     * Tests the allowEmptyTime method
     *
     * @return void
     */
    public function testAllowEmptyTime()
    {
        $validator = new Validator();
        $validator->allowEmptyTime('time')
            ->time('time');

        $this->assertTrue($validator->field('time')->isEmptyAllowed());

        $data = [
            'time' => [
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'time' => '',
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'time' => null,
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = ['time' => []];
        $result = $validator->errors($data);
        $this->assertEmpty($result);
    }

    /**
     * test allowEmptyTime with condition
     *
     * @return void
     */
    public function testAllowEmptyTimeCondition()
    {
        $validator = new Validator();
        $validator->allowEmptyTime('time', 'valid time', 'update');
        $this->assertFalse($validator->isEmptyAllowed('time', true));
        $this->assertTrue($validator->isEmptyAllowed('time', false));

        $data = [
            'time' => null,
        ];
        $expected = [
            'time' => ['_empty' => 'valid time'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * test allowEmptyTime with deprecated argument order
     *
     * @return void
     */
    public function testAllowEmptyTimeConditionDeprecated()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyTime('time', 'update', 'valid time');
        });
        $this->assertFalse($validator->isEmptyAllowed('time', true));
        $this->assertTrue($validator->isEmptyAllowed('time', false));

        $data = [
            'time' => null,
        ];
        $expected = [
            'time' => ['_empty' => 'valid time'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Tests the notEmptyTime method
     *
     * @return void
     */
    public function testNotEmptyTime()
    {
        $validator = new Validator();
        $validator->notEmptyTime('time', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('time', true));
        $this->assertFalse($validator->isEmptyAllowed('time', false));

        $error = ['time' => ['_empty' => 'required field']];
        $data = [
            'time' => [
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['time' => ''];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['time' => null];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['time' => []];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['time' => ['hour' => 12, 'minute' => 12, 'second' => 12]];
        $result = $validator->errors($data);
        $this->assertEmpty($result);
    }

    /**
     * Test notEmptyTime with update mode
     *
     * @return void
     */
    public function testNotEmptyTimeUpdate()
    {
        $validator = new Validator();
        $validator->notEmptyTime('time', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('time', true));
        $this->assertFalse($validator->isEmptyAllowed('time', false));

        $data = ['time' => null];
        $expected = ['time' => ['_empty' => 'message']];
        $this->assertEmpty($validator->errors($data, true));
        $this->assertSame($expected, $validator->errors($data, false));
    }

    /**
     * Tests the allowEmptyDateTime method
     *
     * @return void
     */
    public function testAllowEmptyDateTime()
    {
        $validator = new Validator();
        $validator->allowEmptyDate('published')
            ->dateTime('published');

        $this->assertTrue($validator->field('published')->isEmptyAllowed());

        $data = [
            'published' => [
                'year' => '',
                'month' => '',
                'day' => '',
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'published' => '',
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = [
            'published' => null,
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result);

        $data = ['published' => []];
        $this->assertEmpty($validator->errors($data));
    }

    /**
     * test allowEmptyDateTime with a condition
     *
     * @return void
     */
    public function testAllowEmptyDateTimeCondition()
    {
        $validator = new Validator();
        $validator->allowEmptyDateTime('published', 'datetime required', 'update');
        $this->assertFalse($validator->isEmptyAllowed('published', true));
        $this->assertTrue($validator->isEmptyAllowed('published', false));

        $data = [
            'published' => null,
        ];
        $expected = [
            'published' => ['_empty' => 'datetime required'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * test allowEmptyDateTime with deprecated argument order
     *
     * @return void
     */
    public function testAllowEmptyDateTimeDeprecated()
    {
        $validator = new Validator();
        $this->deprecated(function () use ($validator) {
            $validator->allowEmptyDateTime('published', 'datetime required', 'update');
        });
        $this->assertFalse($validator->isEmptyAllowed('published', true));
        $this->assertTrue($validator->isEmptyAllowed('published', false));

        $data = [
            'published' => null,
        ];
        $expected = [
            'published' => ['_empty' => 'datetime required'],
        ];
        $this->assertSame($expected, $validator->errors($data, true));
        $this->assertEmpty($validator->errors($data, false));
    }

    /**
     * Tests the notEmptyDateTime method
     *
     * @return void
     */
    public function testNotEmptyDateTime()
    {
        $validator = new Validator();
        $validator->notEmptyDateTime('published', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('published', true));
        $this->assertFalse($validator->isEmptyAllowed('published', false));

        $error = ['published' => ['_empty' => 'required field']];
        $data = [
            'published' => [
                'year' => '',
                'month' => '',
                'day' => '',
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['published' => ''];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['published' => null];
        $result = $validator->errors($data);
        $this->assertSame($error, $result);

        $data = ['published' => []];
        $this->assertSame($error, $validator->errors($data));

        $data = [
            'published' => [
                'year' => '2018',
                'month' => '2',
                'day' => '17',
                'hour' => '14',
                'minute' => '32',
                'second' => '33',
            ],
        ];
        $this->assertEmpty($validator->errors($data));
    }

    /**
     * Test notEmptyDateTime with update mode
     *
     * @return voi
     */
    public function testNotEmptyDateTimeUpdate()
    {
        $validator = new Validator();
        $validator->notEmptyDatetime('published', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('published', true));
        $this->assertFalse($validator->isEmptyAllowed('published', false));

        $data = ['published' => null];
        $expected = ['published' => ['_empty' => 'message']];
        $this->assertSame($expected, $validator->errors($data, false));
        $this->assertEmpty($validator->errors($data, true));
    }

    /**
     * Test the notEmpty() method.
     *
     * @return void
     */
    public function testNotEmpty()
    {
        $validator = new Validator();
        $validator->notEmpty('title');
        $this->assertFalse($validator->field('title')->isEmptyAllowed());

        $validator->allowEmpty('title');
        $this->assertTrue($validator->field('title')->isEmptyAllowed());
    }

    /**
     * Tests the notEmpty as array method
     *
     * @return void
     */
    public function testNotEmptyAsArray()
    {
        $validator = new Validator();
        $validator->notEmpty(['title', 'created']);
        $this->assertFalse($validator->field('title')->isEmptyAllowed());
        $this->assertFalse($validator->field('created')->isEmptyAllowed());

        $validator->notEmpty([
            'title' => [
                'when' => false,
            ],
            'content' => [
                'when' => 'update',
            ],
            'posted_at' => [
                'when' => 'create',
            ],
            'show_at' => [
                'message' => 'Show date cannot be empty',
                'when' => false,
            ],
            'subject',
        ], 'Not empty', true);

        $this->assertFalse($validator->field('title')->isEmptyAllowed());
        $this->assertTrue($validator->isEmptyAllowed('content', true));
        $this->assertFalse($validator->isEmptyAllowed('content', false));
        $this->assertFalse($validator->isEmptyAllowed('posted_at', true));
        $this->assertTrue($validator->isEmptyAllowed('posted_at', false));
        $this->assertTrue($validator->field('subject')->isEmptyAllowed());

        $errors = $validator->errors([
            'title' => '',
            'content' => '',
            'posted_at' => null,
            'show_at' => null,
            'subject' => '',
        ], false);

        $expected = [
            'title' => ['_empty' => 'Not empty'],
            'content' => ['_empty' => 'Not empty'],
            'show_at' => ['_empty' => 'Show date cannot be empty'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the notEmpty failure case
     *
     * @return void
     */
    public function testNotEmptyAsArrayFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $validator = new Validator();
        $validator->notEmpty(['title' => 'derp', 'created' => false]);
    }

    /**
     * Test the notEmpty() method.
     *
     * @return void
     */
    public function testNotEmptyModes()
    {
        $validator = new Validator();
        $validator->notEmpty('title', 'Need a title', 'create');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title', 'Need a title', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title', 'Need a title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));
    }

    /**
     * Test interactions between notEmpty() and isAllowed().
     *
     * @return void
     */
    public function testNotEmptyAndIsAllowed()
    {
        $validator = new Validator();
        $validator->allowEmpty('title')
            ->notEmpty('title', 'Need it', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmpty('title')
            ->notEmpty('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title')
            ->allowEmpty('title', 'create');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));
    }

    /**
     * Tests the allowEmpty method when passing a callback
     *
     * @return void
     */
    public function testAllowEmptyCallback()
    {
        $validator = new Validator();
        $allow = true;
        $validator->allowEmpty('title', function ($context) use (&$allow) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertTrue($context['newRecord']);

            return $allow;
        });
        $this->assertTrue($validator->isEmptyAllowed('title', true));

        $allow = false;
        $this->assertFalse($validator->isEmptyAllowed('title', true));
    }

    /**
     * Tests the notEmpty method when passing a callback
     *
     * @return void
     */
    public function testNotEmptyCallback()
    {
        $validator = new Validator();
        $prevent = true;
        $validator->notEmpty('title', 'error message', function ($context) use (&$prevent) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertFalse($context['newRecord']);

            return $prevent;
        });
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $prevent = false;
        $this->assertTrue($validator->isEmptyAllowed('title', false));
    }

    /**
     * Tests the isEmptyAllowed method
     *
     * @return void
     */
    public function testIsEmptyAllowed()
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->allowEmpty('title'));
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmpty('title', 'create');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmpty('title', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', false));
        $this->assertFalse($validator->isEmptyAllowed('title', true));
    }

    /**
     * Tests errors generated when a field is not allowed to be empty
     *
     * @return void
     */
    public function testErrorsWithEmptyNotAllowed()
    {
        $validator = new Validator();
        $validator->notEmpty('title');
        $errors = $validator->errors(['title' => '']);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->errors(['title' => []]);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->errors(['title' => null]);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->errors(['title' => 0]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => '0']);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => false]);
        $this->assertEmpty($errors);
    }

    /**
     * Tests custom error messages generated when a field is allowed to be empty
     *
     * @return void
     */
    public function testCustomErrorsWithAllowedEmpty()
    {
        $validator = new Validator();
        $validator->allowEmpty('title', false, 'Custom message');
        $errors = $validator->errors(['title' => null]);
        $expected = ['title' => ['_empty' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field is not allowed to be empty
     *
     * @return void
     */
    public function testCustomErrorsWithEmptyNotAllowed()
    {
        $validator = new Validator();
        $validator->notEmpty('title', 'Custom message');
        $errors = $validator->errors(['title' => '']);
        $expected = ['title' => ['_empty' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests errors generated when a field is allowed to be empty
     *
     * @return void
     */
    public function testErrorsWithEmptyAllowed()
    {
        $validator = new Validator();
        $validator->allowEmpty('title');
        $errors = $validator->errors(['title' => '']);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => []]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => null]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => 0]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => 0.0]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => '0']);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => false]);
        $this->assertEmpty($errors);
    }

    /**
     * Test the provider() method
     *
     * @return void
     */
    public function testProvider()
    {
        $validator = new Validator();
        $object = new \stdClass();
        $this->assertSame($validator, $validator->setProvider('foo', $object));
        $this->assertSame($object, $validator->getProvider('foo'));
        $this->assertNull($validator->getProvider('bar'));

        $another = new \stdClass();
        $this->assertSame($validator, $validator->setProvider('bar', $another));
        $this->assertSame($another, $validator->getProvider('bar'));

        $this->assertEquals(new \Cake\Validation\RulesProvider(), $validator->getProvider('default'));
    }

    /**
     * Tests errors() method when using validators from the default provider, this proves
     * that it returns a default validation message and the custom one set in the rule
     *
     * @return void
     */
    public function testErrorsFromDefaultProvider()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('email', 'notBlank', ['rule' => 'notBlank'])
            ->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
        $errors = $validator->errors(['email' => 'not an email!']);
        $expected = [
            'email' => [
                'alpha' => 'The provided value is invalid',
                'email' => 'Y u no write email?',
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests using validation methods from different providers and returning the error
     * as a string
     *
     * @return void
     */
    public function testErrorsFromCustomProvider()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);

        $thing = $this->getMockBuilder('\stdClass')
            ->setMethods(['isCool'])
            ->getMock();
        $thing->expects($this->once())->method('isCool')
            ->will($this->returnCallback(function ($data, $context) use ($thing) {
                $this->assertEquals('bar', $data);
                $expected = [
                    'default' => new \Cake\Validation\RulesProvider(),
                    'thing' => $thing,
                ];
                $expected = [
                    'newRecord' => true,
                    'providers' => $expected,
                    'data' => [
                        'email' => '!',
                        'title' => 'bar',
                    ],
                    'field' => 'title',
                ];
                $this->assertEquals($expected, $context);

                return "That ain't cool, yo";
            }));

        $validator->setProvider('thing', $thing);
        $errors = $validator->errors(['email' => '!', 'title' => 'bar']);
        $expected = [
            'email' => ['alpha' => 'The provided value is invalid'],
            'title' => ['cool' => "That ain't cool, yo"],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that it is possible to pass extra arguments to the validation function
     * and it still gets the providers as last argument
     *
     * @return void
     */
    public function testMethodsWithExtraArguments()
    {
        $validator = new Validator();
        $validator->add('title', 'cool', [
            'rule' => ['isCool', 'and', 'awesome'],
            'provider' => 'thing',
        ]);
        $thing = $this->getMockBuilder('\stdClass')
            ->setMethods(['isCool'])
            ->getMock();
        $thing->expects($this->once())->method('isCool')
            ->will($this->returnCallback(function ($data, $a, $b, $context) use ($thing) {
                $this->assertEquals('bar', $data);
                $this->assertEquals('and', $a);
                $this->assertEquals('awesome', $b);
                $expected = [
                    'default' => new \Cake\Validation\RulesProvider(),
                    'thing' => $thing,
                ];
                $expected = [
                    'newRecord' => true,
                    'providers' => $expected,
                    'data' => [
                        'email' => '!',
                        'title' => 'bar',
                    ],
                    'field' => 'title',
                ];
                $this->assertEquals($expected, $context);

                return "That ain't cool, yo";
            }));
        $validator->setProvider('thing', $thing);
        $errors = $validator->errors(['email' => '!', 'title' => 'bar']);
        $expected = [
            'title' => ['cool' => "That ain't cool, yo"],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that it is possible to use a closure as a rule
     *
     * @return void
     */
    public function testUsingClosureAsRule()
    {
        $validator = new Validator();
        $validator->add('name', 'myRule', [
            'rule' => function ($data, $provider) {
                $this->assertEquals('foo', $data);

                return 'You fail';
            },
        ]);
        $expected = ['name' => ['myRule' => 'You fail']];
        $this->assertEquals($expected, $validator->errors(['name' => 'foo']));
    }

    /**
     * Tests that setting last to a rule will stop validating the rest of the rules
     *
     * @return void
     */
    public function testErrorsWithLastRule()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric', 'last' => true])
            ->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
        $errors = $validator->errors(['email' => 'not an email!']);
        $expected = [
            'email' => [
                'alpha' => 'The provided value is invalid',
            ],
        ];

        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests it is possible to get validation sets for a field using an array interface
     *
     * @return void
     */
    public function testArrayAccessGet()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertSame($validator['email'], $validator->field('email'));
        $this->assertSame($validator['title'], $validator->field('title'));
    }

    /**
     * Tests it is possible to check for validation sets for a field using an array interface
     *
     * @return void
     */
    public function testArrayAccessExists()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertArrayHasKey('email', $validator);
        $this->assertArrayHasKey('title', $validator);
        $this->assertArrayNotHasKey('foo', $validator);
    }

    /**
     * Tests it is possible to set validation rules for a field using an array interface
     *
     * @return void
     */
    public function testArrayAccessSet()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $validator['name'] = $validator->field('title');
        $this->assertSame($validator->field('title'), $validator->field('name'));
        $validator['name'] = ['alpha' => ['rule' => 'alphanumeric']];
        $this->assertEquals($validator->field('email'), $validator->field('email'));
    }

    /**
     * Tests it is possible to unset validation rules
     *
     * @return void
     */
    public function testArrayAccessUnset()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertArrayHasKey('title', $validator);
        unset($validator['title']);
        $this->assertArrayNotHasKey('title', $validator);
    }

    /**
     * Tests the countable interface
     *
     * @return void
     */
    public function testCount()
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertCount(2, $validator);
    }

    /**
     * Tests adding rules via alternative syntax
     *
     * @return void
     */
    public function testAddMultiple()
    {
        $validator = new Validator();
        $validator->add('title', [
            'notBlank' => [
                'rule' => 'notBlank',
            ],
            'length' => [
                'rule' => ['minLength', 10],
                'message' => 'Titles need to be at least 10 characters long',
            ],
        ]);
        $set = $validator->field('title');
        $this->assertInstanceOf('Cake\Validation\ValidationSet', $set);
        $this->assertCount(2, $set);
    }

    /**
     * Integration test for compareWith validator.
     *
     * @return void
     */
    public function testCompareWithIntegration()
    {
        $validator = new Validator();
        $validator->add('password', [
            'compare' => [
                'rule' => ['compareWith', 'password_compare'],
            ],
        ]);
        $data = [
            'password' => 'test',
            'password_compare' => 'not the same',
        ];
        $this->assertNotEmpty($validator->errors($data), 'Validation should fail.');
    }

    /**
     * Test debugInfo helper method.
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $validator = new Validator();
        $validator->setProvider('test', $this);
        $validator->add('title', 'not-empty', ['rule' => 'notBlank']);
        $validator->requirePresence('body');
        $validator->allowEmpty('published');

        $result = $validator->__debugInfo();
        $expected = [
            '_providers' => ['test'],
            '_fields' => [
                'title' => [
                    'isPresenceRequired' => false,
                    'isEmptyAllowed' => false,
                    'rules' => ['not-empty'],
                ],
                'body' => [
                    'isPresenceRequired' => true,
                    'isEmptyAllowed' => false,
                    'rules' => [],
                ],
                'published' => [
                    'isPresenceRequired' => false,
                    'isEmptyAllowed' => true,
                    'rules' => [],
                ],
            ],
            '_presenceMessages' => [],
            '_allowEmptyMessages' => [],
            '_allowEmptyFlags' => [],
            '_useI18n' => true,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the 'create' and 'update' modes are preserved when using
     * nested validators
     *
     * @return void
     */
    public function testNestedValidatorCreate()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'email', ['rule' => 'email', 'on' => 'create']);
        $validator->addNested('user', $inner);
        $this->assertNotEmpty($validator->errors(['user' => ['username' => 'example']], true));
        $this->assertEmpty($validator->errors(['user' => ['username' => 'a']], false));
    }

    /**
     * Tests that the 'create' and 'update' modes are preserved when using
     * nested validators
     *
     * @return void
     */
    public function testNestedManyValidatorCreate()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'email', ['rule' => 'email', 'on' => 'create']);
        $validator->addNestedMany('user', $inner);
        $this->assertNotEmpty($validator->errors(['user' => [['username' => 'example']]], true));
        $this->assertEmpty($validator->errors(['user' => [['username' => 'a']]], false));
    }

    /**
     * Tests the notBlank proxy method
     *
     * @return void
     */
    public function testNotBlank()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notBlank');
        $this->assertNotEmpty($validator->errors(['username' => '  ']));
    }

    /**
     * Tests the alphanumeric proxy method
     *
     * @return void
     */
    public function testAlphanumeric()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'alphaNumeric');
        $this->assertNotEmpty($validator->errors(['username' => '$']));
    }

    /**
     * Tests the lengthBetween proxy method
     *
     * @return void
     */
    public function testLengthBetween()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lengthBetween', [5, 7], [5, 7]);
        $this->assertNotEmpty($validator->errors(['username' => 'foo']));
    }

    /**
     * Tests the lengthBetween proxy method
     *
     * @return void
     */
    public function testLengthBetweenFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $validator = new Validator();
        $validator->lengthBetween('username', [7]);
    }

    /**
     * Tests the creditCard proxy method
     *
     * @return void
     */
    public function testCreditCard()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'creditCard', 'all', ['all', true], 'creditCard');
        $this->assertNotEmpty($validator->errors(['username' => 'foo']));
    }

    /**
     * Tests the greaterThan proxy method
     *
     * @return void
     */
    public function testGreaterThan()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThan', 5, [Validation::COMPARE_GREATER, 5], 'comparison');
        $this->assertNotEmpty($validator->errors(['username' => 2]));
    }

    /**
     * Tests the greaterThanOrEqual proxy method
     *
     * @return void
     */
    public function testGreaterThanOrEqual()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThanOrEqual', 5, [Validation::COMPARE_GREATER_OR_EQUAL, 5], 'comparison');
        $this->assertNotEmpty($validator->errors(['username' => 2]));
    }

    /**
     * Tests the lessThan proxy method
     *
     * @return void
     */
    public function testLessThan()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThan', 5, [Validation::COMPARE_LESS, 5], 'comparison');
        $this->assertNotEmpty($validator->errors(['username' => 5]));
    }

    /**
     * Tests the lessThanOrEqual proxy method
     *
     * @return void
     */
    public function testLessThanOrEqual()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThanOrEqual', 5, [Validation::COMPARE_LESS_OR_EQUAL, 5], 'comparison');
        $this->assertNotEmpty($validator->errors(['username' => 6]));
    }

    /**
     * Tests the equals proxy method
     *
     * @return void
     */
    public function testEquals()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'equals', 5, [Validation::COMPARE_EQUAL, 5], 'comparison');
        $this->assertEmpty($validator->errors(['username' => 5]));
        $this->assertNotEmpty($validator->errors(['username' => 6]));
    }

    /**
     * Tests the notEquals proxy method
     *
     * @return void
     */
    public function testNotEquals()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notEquals', 5, [Validation::COMPARE_NOT_EQUAL, 5], 'comparison');
        $this->assertNotEmpty($validator->errors(['username' => 5]));
    }

    /**
     * Tests the sameAs proxy method
     *
     * @return void
     */
    public function testSameAs()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'sameAs', 'other', ['other', Validation::COMPARE_SAME], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 'foo']));
        $this->assertNotEmpty($validator->errors(['username' => 1, 'other' => '1']));
    }

    /**
     * Tests the notSameAs proxy method
     *
     * @return void
     */
    public function testNotSameAs()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notSameAs', 'other', ['other', Validation::COMPARE_NOT_SAME], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 'foo', 'other' => 'foo']));
    }

    /**
     * Tests the equalToField proxy method
     *
     * @return void
     */
    public function testEqualToField()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'equalToField', 'other', ['other', Validation::COMPARE_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 'foo']));
        $this->assertNotEmpty($validator->errors(['username' => 'foo', 'other' => 'bar']));
    }

    /**
     * Tests the notEqualToField proxy method
     *
     * @return void
     */
    public function testNotEqualToField()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notEqualToField', 'other', ['other', Validation::COMPARE_NOT_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 'foo', 'other' => 'foo']));
    }

    /**
     * Tests the greaterThanField proxy method
     *
     * @return void
     */
    public function testGreaterThanField()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThanField', 'other', ['other', Validation::COMPARE_GREATER], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 1, 'other' => 1]));
        $this->assertNotEmpty($validator->errors(['username' => 1, 'other' => 2]));
    }

    /**
     * Tests the greaterThanOrEqualToField proxy method
     *
     * @return void
     */
    public function testGreaterThanOrEqualToField()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThanOrEqualToField', 'other', ['other', Validation::COMPARE_GREATER_OR_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 1, 'other' => 2]));
    }

    /**
     * Tests the lessThanField proxy method
     *
     * @return void
     */
    public function testLessThanField()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThanField', 'other', ['other', Validation::COMPARE_LESS], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 1, 'other' => 1]));
        $this->assertNotEmpty($validator->errors(['username' => 2, 'other' => 1]));
    }

    /**
     * Tests the lessThanOrEqualToField proxy method
     *
     * @return void
     */
    public function testLessThanOrEqualToField()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThanOrEqualToField', 'other', ['other', Validation::COMPARE_LESS_OR_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->errors(['username' => 2, 'other' => 1]));
    }

    /**
     * Tests the containsNonAlphaNumeric proxy method
     *
     * @return void
     */
    public function testContainsNonAlphaNumeric()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'containsNonAlphaNumeric', 2, [2]);
        $this->assertNotEmpty($validator->errors(['username' => '$']));
    }

    /**
     * Tests the date proxy method
     *
     * @return void
     */
    public function testDate()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'date', ['ymd'], [['ymd']]);
        $this->assertNotEmpty($validator->errors(['username' => 'not a date']));
    }

    /**
     * Tests the dateTime proxy method
     *
     * @return void
     */
    public function testDateTime()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'dateTime', ['ymd'], [['ymd']], 'datetime');
        $this->assertNotEmpty($validator->errors(['username' => 'not a date']));
    }

    /**
     * Tests the time proxy method
     *
     * @return void
     */
    public function testTime()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'time');
        $this->assertNotEmpty($validator->errors(['username' => 'not a time']));
    }

    /**
     * Tests the localizedTime proxy method
     *
     * @return void
     */
    public function testLocalizedTime()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'localizedTime', 'date', ['date']);
        $this->assertNotEmpty($validator->errors(['username' => 'not a date']));
    }

    /**
     * Tests the boolean proxy method
     *
     * @return void
     */
    public function testBoolean()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'boolean');
        $this->assertNotEmpty($validator->errors(['username' => 'not a boolean']));
    }

    /**
     * Tests the decimal proxy method
     *
     * @return void
     */
    public function testDecimal()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'decimal', 2, [2]);
        $this->assertNotEmpty($validator->errors(['username' => 10.1]));
    }

    /**
     * Tests the ip proxy methods
     *
     * @return void
     */
    public function testIps()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'ip');
        $this->assertNotEmpty($validator->errors(['username' => 'not ip']));

        $this->assertProxyMethod($validator, 'ipv4', null, ['ipv4'], 'ip');
        $this->assertNotEmpty($validator->errors(['username' => 'not ip']));

        $this->assertProxyMethod($validator, 'ipv6', null, ['ipv6'], 'ip');
        $this->assertNotEmpty($validator->errors(['username' => 'not ip']));
    }

    /**
     * Tests the minLength proxy method
     *
     * @return void
     */
    public function testMinLength()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'minLength', 2, [2]);
        $this->assertNotEmpty($validator->errors(['username' => 'a']));
    }

    /**
     * Tests the minLengthBytes proxy method
     *
     * @return void
     */
    public function testMinLengthBytes()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'minLengthBytes', 11, [11]);
        $this->assertNotEmpty($validator->errors(['username' => 'ÆΔΩЖÇ']));
    }

    /**
     * Tests the maxLength proxy method
     *
     * @return void
     */
    public function testMaxLength()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'maxLength', 2, [2]);
        $this->assertNotEmpty($validator->errors(['username' => 'aaa']));
    }

    /**
     * Tests the maxLengthBytes proxy method
     *
     * @return void
     */
    public function testMaxLengthBytes()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'maxLengthBytes', 9, [9]);
        $this->assertNotEmpty($validator->errors(['username' => 'ÆΔΩЖÇ']));
    }

    /**
     * Tests the numeric proxy method
     *
     * @return void
     */
    public function testNumeric()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'numeric');
        $this->assertEmpty($validator->errors(['username' => '22']));
        $this->assertNotEmpty($validator->errors(['username' => 'a']));
    }

    /**
     * Tests the naturalNumber proxy method
     *
     * @return void
     */
    public function testNaturalNumber()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'naturalNumber', null, [false]);
        $this->assertNotEmpty($validator->errors(['username' => 0]));
    }

    /**
     * Tests the nonNegativeInteger proxy method
     *
     * @return void
     */
    public function testNonNegativeInteger()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'nonNegativeInteger', null, [true], 'naturalNumber');
        $this->assertNotEmpty($validator->errors(['username' => -1]));
    }

    /**
     * Tests the range proxy method
     *
     * @return void
     */
    public function testRange()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'range', [1, 4], [1, 4]);
        $this->assertNotEmpty($validator->errors(['username' => 5]));
    }

    /**
     * Tests the range failure case
     *
     * @return void
     */
    public function testRangeFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $validator = new Validator();
        $validator->range('username', [1]);
    }

    /**
     * Tests the url proxy method
     *
     * @return void
     */
    public function testUrl()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'url', null, [false]);
        $this->assertNotEmpty($validator->errors(['username' => 'not url']));
    }

    /**
     * Tests the urlWithProtocol proxy method
     *
     * @return void
     */
    public function testUrlWithProtocol()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'urlWithProtocol', null, [true], 'url');
        $this->assertNotEmpty($validator->errors(['username' => 'google.com']));
    }

    /**
     * Tests the inList proxy method
     *
     * @return void
     */
    public function testInList()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'inList', ['a', 'b'], [['a', 'b']]);
        $this->assertNotEmpty($validator->errors(['username' => 'c']));
    }

    /**
     * Tests the uuid proxy method
     *
     * @return void
     */
    public function testUuid()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'uuid');
        $this->assertNotEmpty($validator->errors(['username' => 'not uuid']));
    }

    /**
     * Tests the uploadedFile proxy method
     *
     * @return void
     */
    public function testUploadedFile()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'uploadedFile', ['foo' => 'bar'], [['foo' => 'bar']]);
        $this->assertNotEmpty($validator->errors(['username' => []]));
    }

    /**
     * Tests the latlog proxy methods
     *
     * @return void
     */
    public function testLatLong()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'latLong', null, [], 'geoCoordinate');
        $this->assertNotEmpty($validator->errors(['username' => 2000]));

        $this->assertProxyMethod($validator, 'latitude');
        $this->assertNotEmpty($validator->errors(['username' => 2000]));

        $this->assertProxyMethod($validator, 'longitude');
        $this->assertNotEmpty($validator->errors(['username' => 2000]));
    }

    /**
     * Tests the ascii proxy method
     *
     * @return void
     */
    public function testAscii()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'ascii');
        $this->assertNotEmpty($validator->errors(['username' => 'ü']));
    }

    /**
     * Tests the utf8 proxy methods
     *
     * @return void
     */
    public function testUtf8()
    {
        // Grinning face
        $extended = 'some' . "\xf0\x9f\x98\x80" . 'value';
        $validator = new Validator();

        $this->assertProxyMethod($validator, 'utf8', null, [['extended' => false]]);
        $this->assertEmpty($validator->errors(['username' => 'ü']));
        $this->assertNotEmpty($validator->errors(['username' => $extended]));
    }

    /**
     * Test utf8extended proxy method.
     *
     * @return void
     */
    public function testUtf8Extended()
    {
        // Grinning face
        $extended = 'some' . "\xf0\x9f\x98\x80" . 'value';
        $validator = new Validator();

        $this->assertProxyMethod($validator, 'utf8Extended', null, [['extended' => true]], 'utf8');
        $this->assertEmpty($validator->errors(['username' => 'ü']));
        $this->assertEmpty($validator->errors(['username' => $extended]));
    }

    /**
     * Tests the email proxy method
     *
     * @return void
     */
    public function testEmail()
    {
        $validator = new Validator();
        $validator->email('username');
        $this->assertEmpty($validator->errors(['username' => 'test@example.com']));
        $this->assertNotEmpty($validator->errors(['username' => 'not an email']));
    }

    /**
     * Tests the integer proxy method
     *
     * @return void
     */
    public function testInteger()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'integer', null, [], 'isInteger');
        $this->assertNotEmpty($validator->errors(['username' => 'not integer']));
    }

    /**
     * Tests the isArray proxy method
     *
     * @return void
     */
    public function testIsArray()
    {
        $validator = new Validator();
        $validator->isArray('username');
        $this->assertEmpty($validator->errors(['username' => [1, 2, 3]]));
        $this->assertNotEmpty($validator->errors(['username' => 'is not an array']));
    }

    /**
     * Tests the scalar proxy method
     *
     * @return void
     */
    public function testScalar()
    {
        $validator = new Validator();
        $validator->scalar('username');
        $this->assertEmpty($validator->errors(['username' => 'scalar']));
        $this->assertNotEmpty($validator->errors(['username' => ['array']]));
    }

    /**
     * Tests the hexColor proxy method
     *
     * @return void
     */
    public function testHexColor()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'hexColor');
        $this->assertEmpty($validator->errors(['username' => '#FFFFFF']));
        $this->assertNotEmpty($validator->errors(['username' => 'FFFFFF']));
    }

    /**
     * Tests the multiple proxy method
     *
     * @return void
     */
    public function testMultiple()
    {
        $validator = new Validator();
        $this->assertProxyMethod(
            $validator,
            'multipleOptions',
            ['min' => 1, 'caseInsensitive' => true],
            [['min' => 1], true],
            'multiple'
        );

        $this->assertProxyMethod(
            $validator,
            'multipleOptions',
            ['min' => 1, 'caseInsensitive' => false],
            [['min' => 1], false],
            'multiple'
        );

        $this->assertNotEmpty($validator->errors(['username' => '']));
    }

    /**
     * Tests the hasAtLeast method
     *
     * @return void
     */
    public function testHasAtLeast()
    {
        $validator = new Validator();
        $validator->hasAtLeast('things', 3);
        $this->assertEmpty($validator->errors(['things' => [1, 2, 3]]));
        $this->assertEmpty($validator->errors(['things' => [1, 2, 3, 4]]));
        $this->assertNotEmpty($validator->errors(['things' => [1, 2]]));
        $this->assertNotEmpty($validator->errors(['things' => []]));
        $this->assertNotEmpty($validator->errors(['things' => 'string']));

        $this->assertEmpty($validator->errors(['things' => ['_ids' => [1, 2, 3]]]));
        $this->assertEmpty($validator->errors(['things' => ['_ids' => [1, 2, 3, 4]]]));
        $this->assertNotEmpty($validator->errors(['things' => ['_ids' => [1, 2]]]));
        $this->assertNotEmpty($validator->errors(['things' => ['_ids' => []]]));
        $this->assertNotEmpty($validator->errors(['things' => ['_ids' => 'string']]));
    }

    /**
     * Tests the hasAtMost method
     *
     * @return void
     */
    public function testHasAtMost()
    {
        $validator = new Validator();
        $validator->hasAtMost('things', 3);
        $this->assertEmpty($validator->errors(['things' => [1, 2, 3]]));
        $this->assertEmpty($validator->errors(['things' => [1]]));
        $this->assertNotEmpty($validator->errors(['things' => [1, 2, 3, 4]]));

        $this->assertEmpty($validator->errors(['things' => ['_ids' => [1, 2, 3]]]));
        $this->assertEmpty($validator->errors(['things' => ['_ids' => [1, 2]]]));
        $this->assertNotEmpty($validator->errors(['things' => ['_ids' => [1, 2, 3, 4]]]));
    }

    /**
     * Tests the regex proxy method
     *
     * @return void
     */
    public function testRegex()
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'regex', '/(?<!\\S)\\d++(?!\\S)/', ['/(?<!\\S)\\d++(?!\\S)/'], 'custom');
        $this->assertEmpty($validator->errors(['username' => '123']));
        $this->assertNotEmpty($validator->errors(['username' => 'Foo']));
    }

    /**
     * Tests that a rule in the Validator class exists and was configured as expected.
     *
     * @param Validator $validator
     * @param string $method
     * @param mixed $extra
     * @param array $pass
     * @param string|null $name
     */
    protected function assertProxyMethod($validator, $method, $extra = null, $pass = [], $name = null)
    {
        $name = $name ?: $method;
        if ($extra !== null) {
            $this->assertSame($validator, $validator->{$method}('username', $extra));
        } else {
            $this->assertSame($validator, $validator->{$method}('username'));
        }

        $rule = $validator->field('username')->rule($method);
        $this->assertNotEmpty($rule, "Rule was not found for $method");
        $this->assertNull($rule->get('message'), 'Message is present when it should not be');
        $this->assertNull($rule->get('on'), 'On clause is present when it should not be');
        $this->assertEquals($name, $rule->get('rule'), 'Rule name does not match');
        $this->assertEquals($pass, $rule->get('pass'), 'Passed options are different');
        $this->assertEquals('default', $rule->get('provider'), 'Provider does not match');

        if ($extra !== null) {
            $validator->{$method}('username', $extra, 'the message', 'create');
        } else {
            $validator->{$method}('username', 'the message', 'create');
        }

        $rule = $validator->field('username')->rule($method);
        $this->assertEquals('the message', $rule->get('message'), 'Error messages are not the same');
        $this->assertEquals('create', $rule->get('on'), 'On clause is wrong');
    }

    /**
     * Testing adding DefaultProvider
     *
     * @return void
     */
    public function testAddingDefaultProvider()
    {
        $validator = new Validator();
        $this->assertEmpty($validator->providers(), 'Providers should be empty');

        Validator::addDefaultProvider('test-provider', 'MyNameSpace\Validation\MyProvider');
        $validator = new Validator();
        $this->assertEquals($validator->providers(), ['test-provider'], 'Default provider `test-provider` is missing');
    }

    /**
     * Testing getting DefaultProvider(s)
     *
     * @return void
     */
    public function testGetDefaultProvider()
    {
        Validator::addDefaultProvider('test-provider', 'MyNameSpace\Validation\MyProvider');
        $this->assertEquals(Validator::getDefaultProvider('test-provider'), 'MyNameSpace\Validation\MyProvider', 'Default provider `test-provider` is missing');

        $this->assertNull(Validator::getDefaultProvider('invalid-provider'), 'Default provider (`invalid-provider`) should be missing');
        $this->assertNull(Validator::getDefaultProvider(null), 'Default provider (null) should be missing');

        Validator::addDefaultProvider('test-provider2', 'MyNameSpace\Validation\MySecondProvider');
        $this->assertEquals(Validator::getDefaultProviders(), ['test-provider', 'test-provider2'], 'Default providers incorrect');
    }
}
