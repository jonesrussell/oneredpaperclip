# Waaseyaa Conversion Plan 1: Entity Types & Enums

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Define all domain entity types (Challenge, Item, Offer, Trade, Comment, Follow, Notification) and their enums in a new `oneredpaperclip-waaseyaa` repo, with a service provider that registers them with Waaseyaa's entity system.

**Architecture:** Each entity extends `ContentEntityBase`, hardcodes its `entityTypeId` and `entityKeys`, and provides typed getters/setters. A single `TradeUpServiceProvider` registers all entity types via `$this->entityType()`. Enums are backed string enums matching the Laravel originals. Categories use `waaseyaa/taxonomy` directly (no custom entity).

**Tech Stack:** PHP 8.4, Waaseyaa (foundation, entity, entity-storage, taxonomy), PHPUnit 10

**Spec:** `docs/superpowers/specs/2026-03-19-oneredpaperclip-waaseyaa-conversion-design.md`

**Waaseyaa reference patterns:**
- Entity class: see `packages/note/src/Note.php` (extends `ContentEntityBase`, hardcodes `entityTypeId` + `entityKeys`)
- Service provider: see `packages/note/src/NoteServiceProvider.php` (registers `EntityType` with field definitions)
- Entity tests: see `packages/note/tests/Unit/NoteTest.php` (PHPUnit with `#[CoversClass]`, `#[Test]`)

---

## File Structure

```
oneredpaperclip-waaseyaa/
├── composer.json
├── phpunit.xml
├── src/
│   ├── Entity/
│   │   ├── Challenge.php
│   │   ├── Item.php
│   │   ├── Offer.php
│   │   ├── Trade.php
│   │   ├── Comment.php
│   │   ├── Follow.php
│   │   └── Notification.php
│   ├── Enum/
│   │   ├── ChallengeStatus.php
│   │   ├── ChallengeVisibility.php
│   │   ├── ItemRole.php
│   │   ├── OfferStatus.php
│   │   └── TradeStatus.php
│   └── TradeUpServiceProvider.php
└── tests/
    └── Unit/
        ├── Entity/
        │   ├── ChallengeTest.php
        │   ├── ItemTest.php
        │   ├── OfferTest.php
        │   ├── TradeTest.php
        │   ├── CommentTest.php
        │   ├── FollowTest.php
        │   └── NotificationTest.php
        ├── Enum/
        │   ├── ChallengeStatusTest.php
        │   ├── ChallengeVisibilityTest.php
        │   ├── ItemRoleTest.php
        │   ├── OfferStatusTest.php
        │   └── TradeStatusTest.php
        └── TradeUpServiceProviderTest.php
```

---

### Task 1: Scaffold the repo

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml`

- [ ] **Step 1: Create the repo directory**

```bash
mkdir -p /home/fsd42/dev/oneredpaperclip-waaseyaa
cd /home/fsd42/dev/oneredpaperclip-waaseyaa
git init
```

- [ ] **Step 2: Create composer.json**

```json
{
    "name": "jonesrussell/oneredpaperclip-waaseyaa",
    "description": "One Red Paperclip — trade-up platform on Waaseyaa",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=8.4",
        "waaseyaa/foundation": "@dev",
        "waaseyaa/entity": "@dev",
        "waaseyaa/entity-storage": "@dev",
        "waaseyaa/taxonomy": "@dev"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    },
    "repositories": [
        { "type": "path", "url": "../waaseyaa/packages/foundation" },
        { "type": "path", "url": "../waaseyaa/packages/entity" },
        { "type": "path", "url": "../waaseyaa/packages/entity-storage" },
        { "type": "path", "url": "../waaseyaa/packages/taxonomy" },
        { "type": "path", "url": "../waaseyaa/packages/typed-data" },
        { "type": "path", "url": "../waaseyaa/packages/database-legacy" },
        { "type": "path", "url": "../waaseyaa/packages/cache" },
        { "type": "path", "url": "../waaseyaa/packages/plugin" },
        { "type": "path", "url": "../waaseyaa/packages/access" },
        { "type": "path", "url": "../waaseyaa/packages/field" },
        { "type": "path", "url": "../waaseyaa/packages/config" },
        { "type": "path", "url": "../waaseyaa/packages/user" },
        { "type": "path", "url": "../waaseyaa/packages/i18n" },
        { "type": "path", "url": "../waaseyaa/packages/state" },
        { "type": "path", "url": "../waaseyaa/packages/validation" },
        { "type": "path", "url": "../waaseyaa/packages/queue" },
        { "type": "path", "url": "../waaseyaa/packages/testing" }
    ],
    "autoload": {
        "psr-4": {
            "OneRedPaperclip\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "OneRedPaperclip\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 3: Create phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 4: Create directory structure**

```bash
mkdir -p src/Entity src/Enum tests/Unit/Entity tests/Unit/Enum
```

- [ ] **Step 5: Install dependencies**

```bash
composer install
```

Expected: Dependencies resolve successfully. `vendor/` directory created.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock phpunit.xml
git commit -m "chore: scaffold oneredpaperclip-waaseyaa repo"
```

---

### Task 2: Create enums

**Files:**
- Create: `src/Enum/ChallengeStatus.php`
- Create: `src/Enum/ChallengeVisibility.php`
- Create: `src/Enum/ItemRole.php`
- Create: `src/Enum/OfferStatus.php`
- Create: `src/Enum/TradeStatus.php`
- Create: `tests/Unit/Enum/ChallengeStatusTest.php`
- Create: `tests/Unit/Enum/ChallengeVisibilityTest.php`
- Create: `tests/Unit/Enum/ItemRoleTest.php`
- Create: `tests/Unit/Enum/OfferStatusTest.php`
- Create: `tests/Unit/Enum/TradeStatusTest.php`

- [ ] **Step 1: Write all enum tests**

```php
// tests/Unit/Enum/ChallengeStatusTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Enum;

use OneRedPaperclip\Enum\ChallengeStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChallengeStatus::class)]
final class ChallengeStatusTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $cases = array_map(fn ($c) => $c->value, ChallengeStatus::cases());

        $this->assertSame(['draft', 'published', 'completed', 'archived'], $cases);
    }

    #[Test]
    public function canBeCreatedFromValue(): void
    {
        $this->assertSame(ChallengeStatus::Published, ChallengeStatus::from('published'));
    }
}
```

```php
// tests/Unit/Enum/ChallengeVisibilityTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Enum;

use OneRedPaperclip\Enum\ChallengeVisibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChallengeVisibility::class)]
final class ChallengeVisibilityTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $cases = array_map(fn ($c) => $c->value, ChallengeVisibility::cases());

        $this->assertSame(['public', 'private', 'unlisted'], $cases);
    }
}
```

```php
// tests/Unit/Enum/ItemRoleTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Enum;

use OneRedPaperclip\Enum\ItemRole;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ItemRole::class)]
final class ItemRoleTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $cases = array_map(fn ($c) => $c->value, ItemRole::cases());

        $this->assertSame(['start', 'goal', 'offered'], $cases);
    }
}
```

```php
// tests/Unit/Enum/OfferStatusTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Enum;

use OneRedPaperclip\Enum\OfferStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OfferStatus::class)]
final class OfferStatusTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $cases = array_map(fn ($c) => $c->value, OfferStatus::cases());

        $this->assertSame(['pending', 'accepted', 'declined', 'withdrawn', 'expired'], $cases);
    }
}
```

```php
// tests/Unit/Enum/TradeStatusTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Enum;

use OneRedPaperclip\Enum\TradeStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TradeStatus::class)]
final class TradeStatusTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $cases = array_map(fn ($c) => $c->value, TradeStatus::cases());

        $this->assertSame(['pending_confirmation', 'completed', 'disputed'], $cases);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/phpunit tests/Unit/Enum/
```

Expected: 5 errors — enum classes not found.

- [ ] **Step 3: Write all enum implementations**

```php
// src/Enum/ChallengeStatus.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Enum;

enum ChallengeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Completed = 'completed';
    case Archived = 'archived';
}
```

```php
// src/Enum/ChallengeVisibility.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Enum;

enum ChallengeVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
```

```php
// src/Enum/ItemRole.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Enum;

enum ItemRole: string
{
    case Start = 'start';
    case Goal = 'goal';
    case Offered = 'offered';
}
```

```php
// src/Enum/OfferStatus.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Enum;

enum OfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';
}
```

```php
// src/Enum/TradeStatus.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Enum;

enum TradeStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Completed = 'completed';
    case Disputed = 'disputed';
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
vendor/bin/phpunit tests/Unit/Enum/
```

Expected: 6 tests, 6 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Enum/ tests/Unit/Enum/
git commit -m "feat: add domain enums (ChallengeStatus, ChallengeVisibility, ItemRole, OfferStatus, TradeStatus)"
```

---

### Task 3: Create Challenge entity

**Files:**
- Create: `src/Entity/Challenge.php`
- Create: `tests/Unit/Entity/ChallengeTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/ChallengeTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Challenge;
use OneRedPaperclip\Enum\ChallengeStatus;
use OneRedPaperclip\Enum\ChallengeVisibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Challenge::class)]
final class ChallengeTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsChallenge(): void
    {
        $challenge = new Challenge([]);

        $this->assertSame('challenge', $challenge->getEntityTypeId());
    }

    #[Test]
    public function newChallengeIsNew(): void
    {
        $challenge = new Challenge(['title' => 'Test']);

        $this->assertTrue($challenge->isNew());
    }

    #[Test]
    public function existingChallengeIsNotNew(): void
    {
        $challenge = new Challenge(['id' => 1, 'title' => 'Test']);

        $this->assertFalse($challenge->isNew());
    }

    #[Test]
    public function labelReturnsTitle(): void
    {
        $challenge = new Challenge(['title' => 'Red Paperclip']);

        $this->assertSame('Red Paperclip', $challenge->label());
    }

    #[Test]
    public function getTitleReturnsTitle(): void
    {
        $challenge = new Challenge(['title' => 'My Challenge']);

        $this->assertSame('My Challenge', $challenge->getTitle());
    }

    #[Test]
    public function setTitleUpdatesTitle(): void
    {
        $challenge = new Challenge(['title' => 'Old']);
        $challenge->setTitle('New');

        $this->assertSame('New', $challenge->getTitle());
    }

    #[Test]
    public function getSlugReturnsSlug(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'slug' => 'my-challenge']);

        $this->assertSame('my-challenge', $challenge->getSlug());
    }

    #[Test]
    public function getStatusReturnsEnum(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'status' => 'draft']);

        $this->assertSame(ChallengeStatus::Draft, $challenge->getStatus());
    }

    #[Test]
    public function setStatusUpdatesStatus(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'status' => 'draft']);
        $challenge->setStatus(ChallengeStatus::Published);

        $this->assertSame(ChallengeStatus::Published, $challenge->getStatus());
    }

    #[Test]
    public function getVisibilityReturnsEnum(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'visibility' => 'public']);

        $this->assertSame(ChallengeVisibility::Public, $challenge->getVisibility());
    }

    #[Test]
    public function defaultStatusIsDraft(): void
    {
        $challenge = new Challenge(['title' => 'Test']);

        $this->assertSame(ChallengeStatus::Draft, $challenge->getStatus());
    }

    #[Test]
    public function defaultVisibilityIsPublic(): void
    {
        $challenge = new Challenge(['title' => 'Test']);

        $this->assertSame(ChallengeVisibility::Public, $challenge->getVisibility());
    }

    #[Test]
    public function setVisibilityUpdatesVisibility(): void
    {
        $challenge = new Challenge(['title' => 'Test']);
        $challenge->setVisibility(ChallengeVisibility::Private);

        $this->assertSame(ChallengeVisibility::Private, $challenge->getVisibility());
    }

    #[Test]
    public function getUserIdReturnsUserId(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'user_id' => 42]);

        $this->assertSame(42, $challenge->getUserId());
    }

    #[Test]
    public function getCategoryTidReturnsTaxonomyTermId(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'category_tid' => 5]);

        $this->assertSame(5, $challenge->getCategoryTid());
    }

    #[Test]
    public function getCurrentItemIdReturnsId(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'current_item_id' => 10]);

        $this->assertSame(10, $challenge->getCurrentItemId());
    }

    #[Test]
    public function getGoalItemIdReturnsId(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'goal_item_id' => 20]);

        $this->assertSame(20, $challenge->getGoalItemId());
    }

    #[Test]
    public function uuidIsAutoGenerated(): void
    {
        $challenge = new Challenge(['title' => 'Test']);

        $this->assertNotEmpty($challenge->uuid());
    }

    #[Test]
    public function getDescriptionReturnsDescription(): void
    {
        $challenge = new Challenge(['title' => 'Test', 'description' => 'A fun challenge']);

        $this->assertSame('A fun challenge', $challenge->getDescription());
    }

    #[Test]
    public function getDeletedAtReturnsNullByDefault(): void
    {
        $challenge = new Challenge(['title' => 'Test']);

        $this->assertNull($challenge->getDeletedAt());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/ChallengeTest.php
```

Expected: FAIL — `Challenge` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Challenge.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use OneRedPaperclip\Enum\ChallengeStatus;
use OneRedPaperclip\Enum\ChallengeVisibility;
use Waaseyaa\Entity\ContentEntityBase;

final class Challenge extends ContentEntityBase
{
    protected string $entityTypeId = 'challenge';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'title',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        if (!array_key_exists('status', $values)) {
            $values['status'] = ChallengeStatus::Draft->value;
        }
        if (!array_key_exists('visibility', $values)) {
            $values['visibility'] = ChallengeVisibility::Public->value;
        }

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getTitle(): string
    {
        return (string) ($this->get('title') ?? '');
    }

    public function setTitle(string $title): static
    {
        $this->set('title', $title);

        return $this;
    }

    public function getSlug(): string
    {
        return (string) ($this->get('slug') ?? '');
    }

    public function getDescription(): string
    {
        return (string) ($this->get('description') ?? '');
    }

    public function getStatus(): ChallengeStatus
    {
        return ChallengeStatus::from((string) $this->get('status'));
    }

    public function setStatus(ChallengeStatus $status): static
    {
        $this->set('status', $status->value);

        return $this;
    }

    public function getVisibility(): ChallengeVisibility
    {
        return ChallengeVisibility::from((string) $this->get('visibility'));
    }

    public function setVisibility(ChallengeVisibility $visibility): static
    {
        $this->set('visibility', $visibility->value);

        return $this;
    }

    public function getUserId(): ?int
    {
        $val = $this->get('user_id');

        return $val !== null ? (int) $val : null;
    }

    public function getCategoryTid(): ?int
    {
        $val = $this->get('category_tid');

        return $val !== null ? (int) $val : null;
    }

    public function getCurrentItemId(): ?int
    {
        $val = $this->get('current_item_id');

        return $val !== null ? (int) $val : null;
    }

    public function setCurrentItemId(int $id): static
    {
        $this->set('current_item_id', $id);

        return $this;
    }

    public function getGoalItemId(): ?int
    {
        $val = $this->get('goal_item_id');

        return $val !== null ? (int) $val : null;
    }

    public function getDeletedAt(): ?string
    {
        return $this->get('deleted_at');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/ChallengeTest.php
```

Expected: 19 tests, 19 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Challenge.php tests/Unit/Entity/ChallengeTest.php
git commit -m "feat: add Challenge entity with status/visibility enums"
```

---

### Task 4: Create Item entity

**Files:**
- Create: `src/Entity/Item.php`
- Create: `tests/Unit/Entity/ItemTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/ItemTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Item;
use OneRedPaperclip\Enum\ItemRole;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Item::class)]
final class ItemTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsItem(): void
    {
        $item = new Item([]);

        $this->assertSame('item', $item->getEntityTypeId());
    }

    #[Test]
    public function newItemIsNew(): void
    {
        $item = new Item(['title' => 'Paperclip']);

        $this->assertTrue($item->isNew());
    }

    #[Test]
    public function labelReturnsTitle(): void
    {
        $item = new Item(['title' => 'Red Paperclip']);

        $this->assertSame('Red Paperclip', $item->label());
    }

    #[Test]
    public function getTitleReturnsTitle(): void
    {
        $item = new Item(['title' => 'Pen']);

        $this->assertSame('Pen', $item->getTitle());
    }

    #[Test]
    public function getRoleReturnsEnum(): void
    {
        $item = new Item(['title' => 'Pen', 'role' => 'start']);

        $this->assertSame(ItemRole::Start, $item->getRole());
    }

    #[Test]
    public function getRoleReturnsNullWhenNotSet(): void
    {
        $item = new Item(['title' => 'Pen']);

        $this->assertNull($item->getRole());
    }

    #[Test]
    public function getItemableTypeReturnsType(): void
    {
        $item = new Item(['title' => 'Pen', 'itemable_type' => 'challenge']);

        $this->assertSame('challenge', $item->getItemableType());
    }

    #[Test]
    public function getItemableIdReturnsId(): void
    {
        $item = new Item(['title' => 'Pen', 'itemable_id' => 5]);

        $this->assertSame(5, $item->getItemableId());
    }

    #[Test]
    public function getEstimatedValueReturnsValue(): void
    {
        $item = new Item(['title' => 'Pen', 'estimated_value' => '25.50']);

        $this->assertSame('25.50', $item->getEstimatedValue());
    }

    #[Test]
    public function getDescriptionReturnsEmptyStringByDefault(): void
    {
        $item = new Item(['title' => 'Pen']);

        $this->assertSame('', $item->getDescription());
    }

    #[Test]
    public function setTitleUpdatesTitle(): void
    {
        $item = new Item(['title' => 'Old']);
        $item->setTitle('New');

        $this->assertSame('New', $item->getTitle());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/ItemTest.php
```

Expected: FAIL — `Item` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Item.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use OneRedPaperclip\Enum\ItemRole;
use Waaseyaa\Entity\ContentEntityBase;

final class Item extends ContentEntityBase
{
    protected string $entityTypeId = 'item';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'title',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getTitle(): string
    {
        return (string) ($this->get('title') ?? '');
    }

    public function setTitle(string $title): static
    {
        $this->set('title', $title);

        return $this;
    }

    public function getDescription(): string
    {
        return (string) ($this->get('description') ?? '');
    }

    public function getRole(): ?ItemRole
    {
        $val = $this->get('role');

        return $val !== null ? ItemRole::from((string) $val) : null;
    }

    public function getItemableType(): string
    {
        return (string) ($this->get('itemable_type') ?? '');
    }

    public function getItemableId(): ?int
    {
        $val = $this->get('itemable_id');

        return $val !== null ? (int) $val : null;
    }

    public function getEstimatedValue(): ?string
    {
        return $this->get('estimated_value');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/ItemTest.php
```

Expected: 11 tests, 11 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Item.php tests/Unit/Entity/ItemTest.php
git commit -m "feat: add Item entity with polymorphic itemable fields"
```

---

### Task 5: Create Offer entity

**Files:**
- Create: `src/Entity/Offer.php`
- Create: `tests/Unit/Entity/OfferTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/OfferTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Offer;
use OneRedPaperclip\Enum\OfferStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Offer::class)]
final class OfferTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsOffer(): void
    {
        $offer = new Offer([]);

        $this->assertSame('offer', $offer->getEntityTypeId());
    }

    #[Test]
    public function defaultStatusIsPending(): void
    {
        $offer = new Offer([]);

        $this->assertSame(OfferStatus::Pending, $offer->getStatus());
    }

    #[Test]
    public function setStatusUpdatesStatus(): void
    {
        $offer = new Offer([]);
        $offer->setStatus(OfferStatus::Accepted);

        $this->assertSame(OfferStatus::Accepted, $offer->getStatus());
    }

    #[Test]
    public function getUserIdReturnsUserId(): void
    {
        $offer = new Offer(['user_id' => 3]);

        $this->assertSame(3, $offer->getUserId());
    }

    #[Test]
    public function getChallengeIdReturnsChallengeId(): void
    {
        $offer = new Offer(['challenge_id' => 7]);

        $this->assertSame(7, $offer->getChallengeId());
    }

    #[Test]
    public function getMessageReturnsMessage(): void
    {
        $offer = new Offer(['message' => 'I have a great trade!']);

        $this->assertSame('I have a great trade!', $offer->getMessage());
    }

    #[Test]
    public function getItemIdReturnsItemId(): void
    {
        $offer = new Offer(['item_id' => 10]);

        $this->assertSame(10, $offer->getItemId());
    }

    #[Test]
    public function getTargetItemIdReturnsTargetItemId(): void
    {
        $offer = new Offer(['target_item_id' => 15]);

        $this->assertSame(15, $offer->getTargetItemId());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/OfferTest.php
```

Expected: FAIL — `Offer` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Offer.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use OneRedPaperclip\Enum\OfferStatus;
use Waaseyaa\Entity\ContentEntityBase;

final class Offer extends ContentEntityBase
{
    protected string $entityTypeId = 'offer';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'id',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        if (!array_key_exists('status', $values)) {
            $values['status'] = OfferStatus::Pending->value;
        }

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getStatus(): OfferStatus
    {
        return OfferStatus::from((string) $this->get('status'));
    }

    public function setStatus(OfferStatus $status): static
    {
        $this->set('status', $status->value);

        return $this;
    }

    public function getUserId(): ?int
    {
        $val = $this->get('user_id');

        return $val !== null ? (int) $val : null;
    }

    public function getChallengeId(): ?int
    {
        $val = $this->get('challenge_id');

        return $val !== null ? (int) $val : null;
    }

    public function getItemId(): ?int
    {
        $val = $this->get('item_id');

        return $val !== null ? (int) $val : null;
    }

    public function getTargetItemId(): ?int
    {
        $val = $this->get('target_item_id');

        return $val !== null ? (int) $val : null;
    }

    public function getMessage(): string
    {
        return (string) ($this->get('message') ?? '');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/OfferTest.php
```

Expected: 8 tests, 8 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Offer.php tests/Unit/Entity/OfferTest.php
git commit -m "feat: add Offer entity with status and relationship fields"
```

---

### Task 6: Create Trade entity

**Files:**
- Create: `src/Entity/Trade.php`
- Create: `tests/Unit/Entity/TradeTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/TradeTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Trade;
use OneRedPaperclip\Enum\TradeStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Trade::class)]
final class TradeTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsTrade(): void
    {
        $trade = new Trade([]);

        $this->assertSame('trade', $trade->getEntityTypeId());
    }

    #[Test]
    public function defaultStatusIsPendingConfirmation(): void
    {
        $trade = new Trade([]);

        $this->assertSame(TradeStatus::PendingConfirmation, $trade->getStatus());
    }

    #[Test]
    public function setStatusUpdatesStatus(): void
    {
        $trade = new Trade([]);
        $trade->setStatus(TradeStatus::Completed);

        $this->assertSame(TradeStatus::Completed, $trade->getStatus());
    }

    #[Test]
    public function getChallengeIdReturnsChallengeId(): void
    {
        $trade = new Trade(['challenge_id' => 5]);

        $this->assertSame(5, $trade->getChallengeId());
    }

    #[Test]
    public function getOfferIdReturnsOfferId(): void
    {
        $trade = new Trade(['offer_id' => 8]);

        $this->assertSame(8, $trade->getOfferId());
    }

    #[Test]
    public function getPositionReturnsPosition(): void
    {
        $trade = new Trade(['position' => 3]);

        $this->assertSame(3, $trade->getPosition());
    }

    #[Test]
    public function confirmByOwnerSetsTimestamp(): void
    {
        $trade = new Trade([]);
        $now = '2026-03-19 12:00:00';
        $trade->confirmByOwner($now);

        $this->assertSame($now, $trade->getConfirmedByOwnerAt());
    }

    #[Test]
    public function confirmByOffererSetsTimestamp(): void
    {
        $trade = new Trade([]);
        $now = '2026-03-19 12:00:00';
        $trade->confirmByOfferer($now);

        $this->assertSame($now, $trade->getConfirmedByOffererAt());
    }

    #[Test]
    public function confirmationsAreNullByDefault(): void
    {
        $trade = new Trade([]);

        $this->assertNull($trade->getConfirmedByOwnerAt());
        $this->assertNull($trade->getConfirmedByOffererAt());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/TradeTest.php
```

Expected: FAIL — `Trade` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Trade.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use OneRedPaperclip\Enum\TradeStatus;
use Waaseyaa\Entity\ContentEntityBase;

final class Trade extends ContentEntityBase
{
    protected string $entityTypeId = 'trade';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'id',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        if (!array_key_exists('status', $values)) {
            $values['status'] = TradeStatus::PendingConfirmation->value;
        }

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getStatus(): TradeStatus
    {
        return TradeStatus::from((string) $this->get('status'));
    }

    public function setStatus(TradeStatus $status): static
    {
        $this->set('status', $status->value);

        return $this;
    }

    public function getChallengeId(): ?int
    {
        $val = $this->get('challenge_id');

        return $val !== null ? (int) $val : null;
    }

    public function getOfferId(): ?int
    {
        $val = $this->get('offer_id');

        return $val !== null ? (int) $val : null;
    }

    public function getPosition(): ?int
    {
        $val = $this->get('position');

        return $val !== null ? (int) $val : null;
    }

    public function getConfirmedByOwnerAt(): ?string
    {
        return $this->get('confirmed_by_owner_at');
    }

    public function confirmByOwner(string $timestamp): static
    {
        $this->set('confirmed_by_owner_at', $timestamp);

        return $this;
    }

    public function getConfirmedByOffererAt(): ?string
    {
        return $this->get('confirmed_by_offerer_at');
    }

    public function confirmByOfferer(string $timestamp): static
    {
        $this->set('confirmed_by_offerer_at', $timestamp);

        return $this;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/TradeTest.php
```

Expected: 9 tests, 10 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Trade.php tests/Unit/Entity/TradeTest.php
git commit -m "feat: add Trade entity with confirmation timestamps"
```

---

### Task 7: Create Comment entity

**Files:**
- Create: `src/Entity/Comment.php`
- Create: `tests/Unit/Entity/CommentTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/CommentTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Comment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comment::class)]
final class CommentTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsComment(): void
    {
        $comment = new Comment([]);

        $this->assertSame('comment', $comment->getEntityTypeId());
    }

    #[Test]
    public function getBodyReturnsBody(): void
    {
        $comment = new Comment(['body' => 'Great trade!']);

        $this->assertSame('Great trade!', $comment->getBody());
    }

    #[Test]
    public function getUserIdReturnsUserId(): void
    {
        $comment = new Comment(['user_id' => 5]);

        $this->assertSame(5, $comment->getUserId());
    }

    #[Test]
    public function getParentIdReturnsNullForTopLevel(): void
    {
        $comment = new Comment(['body' => 'Top level']);

        $this->assertNull($comment->getParentId());
    }

    #[Test]
    public function getParentIdReturnsParentIdForReply(): void
    {
        $comment = new Comment(['body' => 'Reply', 'parent_id' => 10]);

        $this->assertSame(10, $comment->getParentId());
    }

    #[Test]
    public function getCommentableTypeReturnsType(): void
    {
        $comment = new Comment(['body' => 'Test', 'commentable_type' => 'challenge']);

        $this->assertSame('challenge', $comment->getCommentableType());
    }

    #[Test]
    public function getCommentableIdReturnsId(): void
    {
        $comment = new Comment(['body' => 'Test', 'commentable_id' => 3]);

        $this->assertSame(3, $comment->getCommentableId());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/CommentTest.php
```

Expected: FAIL — `Comment` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Comment.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use Waaseyaa\Entity\ContentEntityBase;

final class Comment extends ContentEntityBase
{
    protected string $entityTypeId = 'comment';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'body',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getBody(): string
    {
        return (string) ($this->get('body') ?? '');
    }

    public function getUserId(): ?int
    {
        $val = $this->get('user_id');

        return $val !== null ? (int) $val : null;
    }

    public function getParentId(): ?int
    {
        $val = $this->get('parent_id');

        return $val !== null ? (int) $val : null;
    }

    public function getCommentableType(): string
    {
        return (string) ($this->get('commentable_type') ?? '');
    }

    public function getCommentableId(): ?int
    {
        $val = $this->get('commentable_id');

        return $val !== null ? (int) $val : null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/CommentTest.php
```

Expected: 7 tests, 7 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Comment.php tests/Unit/Entity/CommentTest.php
git commit -m "feat: add Comment entity with polymorphic commentable and nested replies"
```

---

### Task 8: Create Follow entity

**Files:**
- Create: `src/Entity/Follow.php`
- Create: `tests/Unit/Entity/FollowTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/FollowTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Follow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Follow::class)]
final class FollowTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsFollow(): void
    {
        $follow = new Follow([]);

        $this->assertSame('follow', $follow->getEntityTypeId());
    }

    #[Test]
    public function getUserIdReturnsUserId(): void
    {
        $follow = new Follow(['user_id' => 3]);

        $this->assertSame(3, $follow->getUserId());
    }

    #[Test]
    public function getFollowableTypeReturnsType(): void
    {
        $follow = new Follow(['followable_type' => 'challenge']);

        $this->assertSame('challenge', $follow->getFollowableType());
    }

    #[Test]
    public function getFollowableIdReturnsId(): void
    {
        $follow = new Follow(['followable_id' => 7]);

        $this->assertSame(7, $follow->getFollowableId());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/FollowTest.php
```

Expected: FAIL — `Follow` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Follow.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use Waaseyaa\Entity\ContentEntityBase;

final class Follow extends ContentEntityBase
{
    protected string $entityTypeId = 'follow';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'id',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getUserId(): ?int
    {
        $val = $this->get('user_id');

        return $val !== null ? (int) $val : null;
    }

    public function getFollowableType(): string
    {
        return (string) ($this->get('followable_type') ?? '');
    }

    public function getFollowableId(): ?int
    {
        $val = $this->get('followable_id');

        return $val !== null ? (int) $val : null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/FollowTest.php
```

Expected: 4 tests, 4 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Follow.php tests/Unit/Entity/FollowTest.php
git commit -m "feat: add Follow entity with polymorphic followable"
```

---

### Task 9: Create Notification entity

**Files:**
- Create: `src/Entity/Notification.php`
- Create: `tests/Unit/Entity/NotificationTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Entity/NotificationTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit\Entity;

use OneRedPaperclip\Entity\Notification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Notification::class)]
final class NotificationTest extends TestCase
{
    #[Test]
    public function entityTypeIdIsNotification(): void
    {
        $notification = new Notification([]);

        $this->assertSame('notification', $notification->getEntityTypeId());
    }

    #[Test]
    public function getUserIdReturnsUserId(): void
    {
        $notification = new Notification(['user_id' => 5]);

        $this->assertSame(5, $notification->getUserId());
    }

    #[Test]
    public function getTypeReturnsType(): void
    {
        $notification = new Notification(['type' => 'OfferAccepted']);

        $this->assertSame('OfferAccepted', $notification->getType());
    }

    #[Test]
    public function getDataReturnsDecodedArray(): void
    {
        $data = ['challenge_id' => 1, 'offer_id' => 2];
        $notification = new Notification(['data' => $data]);

        $this->assertSame($data, $notification->getData());
    }

    #[Test]
    public function isUnreadWhenReadAtIsNull(): void
    {
        $notification = new Notification([]);

        $this->assertTrue($notification->isUnread());
    }

    #[Test]
    public function isReadWhenReadAtIsSet(): void
    {
        $notification = new Notification(['read_at' => '2026-03-19 12:00:00']);

        $this->assertFalse($notification->isUnread());
    }

    #[Test]
    public function markAsReadSetsTimestamp(): void
    {
        $notification = new Notification([]);
        $notification->markAsRead('2026-03-19 12:00:00');

        $this->assertFalse($notification->isUnread());
        $this->assertSame('2026-03-19 12:00:00', $notification->getReadAt());
    }

    #[Test]
    public function uuidIsAutoGenerated(): void
    {
        $notification = new Notification([]);

        $this->assertNotEmpty($notification->uuid());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/Entity/NotificationTest.php
```

Expected: FAIL — `Notification` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/Entity/Notification.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Entity;

use Waaseyaa\Entity\ContentEntityBase;

final class Notification extends ContentEntityBase
{
    protected string $entityTypeId = 'notification';

    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'type',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getUserId(): ?int
    {
        $val = $this->get('user_id');

        return $val !== null ? (int) $val : null;
    }

    public function getType(): string
    {
        return (string) ($this->get('type') ?? '');
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        $data = $this->get('data');

        if (\is_array($data)) {
            return $data;
        }

        if (\is_string($data)) {
            return json_decode($data, true) ?? [];
        }

        return [];
    }

    public function getReadAt(): ?string
    {
        return $this->get('read_at');
    }

    public function isUnread(): bool
    {
        return $this->getReadAt() === null;
    }

    public function markAsRead(string $timestamp): static
    {
        $this->set('read_at', $timestamp);

        return $this;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/Entity/NotificationTest.php
```

Expected: 8 tests, 9 assertions, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Entity/Notification.php tests/Unit/Entity/NotificationTest.php
git commit -m "feat: add Notification entity with read/unread tracking"
```

---

### Task 10: Create TradeUpServiceProvider

**Files:**
- Create: `src/TradeUpServiceProvider.php`
- Create: `tests/Unit/TradeUpServiceProviderTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/TradeUpServiceProviderTest.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip\Tests\Unit;

use OneRedPaperclip\Entity\Challenge;
use OneRedPaperclip\Entity\Comment;
use OneRedPaperclip\Entity\Follow;
use OneRedPaperclip\Entity\Item;
use OneRedPaperclip\Entity\Notification;
use OneRedPaperclip\Entity\Offer;
use OneRedPaperclip\Entity\Trade;
use OneRedPaperclip\TradeUpServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TradeUpServiceProvider::class)]
final class TradeUpServiceProviderTest extends TestCase
{
    private TradeUpServiceProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new TradeUpServiceProvider();
        $this->provider->register();
    }

    #[Test]
    public function registersSevenEntityTypes(): void
    {
        $this->assertCount(7, $this->provider->getEntityTypes());
    }

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function entityTypeProvider(): array
    {
        return [
            'challenge' => ['challenge', Challenge::class],
            'item' => ['item', Item::class],
            'offer' => ['offer', Offer::class],
            'trade' => ['trade', Trade::class],
            'comment' => ['comment', Comment::class],
            'follow' => ['follow', Follow::class],
            'notification' => ['notification', Notification::class],
        ];
    }

    #[Test]
    #[DataProvider('entityTypeProvider')]
    public function registersEntityType(string $id, string $class): void
    {
        $types = $this->provider->getEntityTypes();
        $found = null;

        foreach ($types as $type) {
            if ($type->id() === $id) {
                $found = $type;
                break;
            }
        }

        $this->assertNotNull($found, "Entity type '$id' not registered");
        $this->assertSame($class, $found->getClass());
    }

    #[Test]
    public function challengeEntityTypeHasFieldDefinitions(): void
    {
        $types = $this->provider->getEntityTypes();
        $challenge = null;

        foreach ($types as $type) {
            if ($type->id() === 'challenge') {
                $challenge = $type;
                break;
            }
        }

        $this->assertNotNull($challenge);
        $fields = $challenge->getFieldDefinitions();
        $this->assertArrayHasKey('title', $fields);
        $this->assertArrayHasKey('slug', $fields);
        $this->assertArrayHasKey('status', $fields);
        $this->assertArrayHasKey('visibility', $fields);
        $this->assertArrayHasKey('user_id', $fields);
        $this->assertArrayHasKey('category_tid', $fields);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Unit/TradeUpServiceProviderTest.php
```

Expected: FAIL — `TradeUpServiceProvider` class not found.

- [ ] **Step 3: Write the implementation**

```php
// src/TradeUpServiceProvider.php
<?php

declare(strict_types=1);

namespace OneRedPaperclip;

use OneRedPaperclip\Entity\Challenge;
use OneRedPaperclip\Entity\Comment;
use OneRedPaperclip\Entity\Follow;
use OneRedPaperclip\Entity\Item;
use OneRedPaperclip\Entity\Notification;
use OneRedPaperclip\Entity\Offer;
use OneRedPaperclip\Entity\Trade;
use Waaseyaa\Entity\EntityType;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;

final class TradeUpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->entityType(new EntityType(
            id: 'challenge',
            label: 'Challenge',
            class: Challenge::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'title'],
            group: 'content',
            fieldDefinitions: [
                'title' => [
                    'type' => 'string',
                    'label' => 'Title',
                    'required' => true,
                    'weight' => 0,
                ],
                'slug' => [
                    'type' => 'string',
                    'label' => 'Slug',
                    'required' => true,
                    'weight' => 1,
                ],
                'description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'required' => false,
                    'weight' => 2,
                ],
                'status' => [
                    'type' => 'string',
                    'label' => 'Status',
                    'required' => true,
                    'weight' => 3,
                ],
                'visibility' => [
                    'type' => 'string',
                    'label' => 'Visibility',
                    'required' => true,
                    'weight' => 4,
                ],
                'user_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Owner',
                    'target_entity_type_id' => 'user',
                    'weight' => 5,
                ],
                'category_tid' => [
                    'type' => 'entity_reference',
                    'label' => 'Category',
                    'target_entity_type_id' => 'taxonomy_term',
                    'weight' => 6,
                ],
                'current_item_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Current Item',
                    'target_entity_type_id' => 'item',
                    'weight' => 7,
                ],
                'goal_item_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Goal Item',
                    'target_entity_type_id' => 'item',
                    'weight' => 8,
                ],
                'deleted_at' => [
                    'type' => 'timestamp',
                    'label' => 'Deleted at',
                    'required' => false,
                    'weight' => 20,
                ],
            ],
        ));

        $this->entityType(new EntityType(
            id: 'item',
            label: 'Item',
            class: Item::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'title'],
            group: 'content',
            fieldDefinitions: [
                'title' => [
                    'type' => 'string',
                    'label' => 'Title',
                    'required' => true,
                    'weight' => 0,
                ],
                'description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'required' => false,
                    'weight' => 1,
                ],
                'role' => [
                    'type' => 'string',
                    'label' => 'Role',
                    'required' => true,
                    'weight' => 2,
                ],
                'itemable_type' => [
                    'type' => 'string',
                    'label' => 'Itemable Type',
                    'required' => true,
                    'weight' => 3,
                ],
                'itemable_id' => [
                    'type' => 'integer',
                    'label' => 'Itemable ID',
                    'required' => true,
                    'weight' => 4,
                ],
                'estimated_value' => [
                    'type' => 'string',
                    'label' => 'Estimated Value',
                    'required' => false,
                    'weight' => 5,
                ],
            ],
        ));

        $this->entityType(new EntityType(
            id: 'offer',
            label: 'Offer',
            class: Offer::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'id'],
            group: 'content',
            fieldDefinitions: [
                'user_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Offerer',
                    'target_entity_type_id' => 'user',
                    'weight' => 0,
                ],
                'challenge_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Challenge',
                    'target_entity_type_id' => 'challenge',
                    'weight' => 1,
                ],
                'item_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Offered Item',
                    'target_entity_type_id' => 'item',
                    'weight' => 2,
                ],
                'target_item_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Target Item',
                    'target_entity_type_id' => 'item',
                    'weight' => 3,
                ],
                'status' => [
                    'type' => 'string',
                    'label' => 'Status',
                    'required' => true,
                    'weight' => 4,
                ],
                'message' => [
                    'type' => 'text',
                    'label' => 'Message',
                    'required' => false,
                    'weight' => 5,
                ],
            ],
        ));

        $this->entityType(new EntityType(
            id: 'trade',
            label: 'Trade',
            class: Trade::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'id'],
            group: 'content',
            fieldDefinitions: [
                'challenge_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Challenge',
                    'target_entity_type_id' => 'challenge',
                    'weight' => 0,
                ],
                'offer_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Offer',
                    'target_entity_type_id' => 'offer',
                    'weight' => 1,
                ],
                'position' => [
                    'type' => 'integer',
                    'label' => 'Position',
                    'required' => true,
                    'weight' => 2,
                ],
                'status' => [
                    'type' => 'string',
                    'label' => 'Status',
                    'required' => true,
                    'weight' => 3,
                ],
                'confirmed_by_owner_at' => [
                    'type' => 'timestamp',
                    'label' => 'Confirmed by Owner',
                    'required' => false,
                    'weight' => 4,
                ],
                'confirmed_by_offerer_at' => [
                    'type' => 'timestamp',
                    'label' => 'Confirmed by Offerer',
                    'required' => false,
                    'weight' => 5,
                ],
            ],
        ));

        $this->entityType(new EntityType(
            id: 'comment',
            label: 'Comment',
            class: Comment::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'body'],
            group: 'content',
            fieldDefinitions: [
                'body' => [
                    'type' => 'text',
                    'label' => 'Body',
                    'required' => true,
                    'weight' => 0,
                ],
                'user_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Author',
                    'target_entity_type_id' => 'user',
                    'weight' => 1,
                ],
                'commentable_type' => [
                    'type' => 'string',
                    'label' => 'Commentable Type',
                    'required' => true,
                    'weight' => 2,
                ],
                'commentable_id' => [
                    'type' => 'integer',
                    'label' => 'Commentable ID',
                    'required' => true,
                    'weight' => 3,
                ],
                'parent_id' => [
                    'type' => 'integer',
                    'label' => 'Parent Comment',
                    'required' => false,
                    'weight' => 4,
                ],
            ],
        ));

        $this->entityType(new EntityType(
            id: 'follow',
            label: 'Follow',
            class: Follow::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'id'],
            group: 'content',
            fieldDefinitions: [
                'user_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Follower',
                    'target_entity_type_id' => 'user',
                    'weight' => 0,
                ],
                'followable_type' => [
                    'type' => 'string',
                    'label' => 'Followable Type',
                    'required' => true,
                    'weight' => 1,
                ],
                'followable_id' => [
                    'type' => 'integer',
                    'label' => 'Followable ID',
                    'required' => true,
                    'weight' => 2,
                ],
            ],
        ));

        $this->entityType(new EntityType(
            id: 'notification',
            label: 'Notification',
            class: Notification::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'type'],
            group: 'content',
            fieldDefinitions: [
                'user_id' => [
                    'type' => 'entity_reference',
                    'label' => 'Recipient',
                    'target_entity_type_id' => 'user',
                    'weight' => 0,
                ],
                'type' => [
                    'type' => 'string',
                    'label' => 'Type',
                    'required' => true,
                    'weight' => 1,
                ],
                'data' => [
                    'type' => 'map',
                    'label' => 'Data',
                    'required' => false,
                    'weight' => 2,
                ],
                'read_at' => [
                    'type' => 'timestamp',
                    'label' => 'Read At',
                    'required' => false,
                    'weight' => 3,
                ],
            ],
        ));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Unit/TradeUpServiceProviderTest.php
```

Expected: 9 tests, all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/TradeUpServiceProvider.php tests/Unit/TradeUpServiceProviderTest.php
git commit -m "feat: add TradeUpServiceProvider registering all 7 entity types"
```

---

### Task 11: Run full test suite

- [ ] **Step 1: Run all tests**

```bash
vendor/bin/phpunit
```

Expected: All tests pass (approximately 70+ tests, 70+ assertions).

- [ ] **Step 2: Verify no errors or warnings**

Check output for deprecation warnings or risky test notices. Fix if any.

- [ ] **Step 3: Final commit if any fixes were needed**

```bash
git add -A
git commit -m "chore: fix any test warnings"
```
