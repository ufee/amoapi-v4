<?php

declare(strict_types=1);

namespace Ufee\AmoV4\Tests\Unit\Collections;

use Ufee\AmoV4\Collections\Collection;
use Ufee\AmoV4\Tests\TestCase;

class CollectionTest extends TestCase
{
	public function testBasicAccessors(): void
	{
		$c = new Collection(['a', 'b', 'c']);
		$this->assertSame(3, $c->count());
		$this->assertSame('a', $c->first());
		$this->assertSame('c', $c->last());
		$this->assertSame(['a', 'b', 'c'], $c->all());
		$this->assertTrue($c->has([0, 2]));
		$this->assertFalse($c->has(5));
		$this->assertSame('b', $c->get(1));
	}

	public function testPushMergeRemoveSlice(): void
	{
		$c = new Collection([1]);
		$c->push(2)->push(3, 'k');
		$this->assertSame(3, $c->get('k'));

		$c->merge(new Collection([4]));
		$this->assertContains(4, $c->all());

		$c->remove('k');
		$this->assertNull($c->get('k'));

		$c2 = new Collection([1, 2, 3, 4]);
		$c2->slice(1, 2);
		$this->assertSame([2, 3], array_values($c2->all()));
	}

	public function testFilterMapFindWhere(): void
	{
		$c = new Collection([
			(object) ['id' => 1, 'name' => 'A'],
			(object) ['id' => 2, 'name' => 'B'],
			(object) ['id' => 3, 'name' => 'A'],
		]);

		$filtered = $c->filter(function ($item) {
			return $item->name === 'A';
		});
		$this->assertCount(2, $filtered);

		$ids = $c->map(function ($item) {
			return $item->id;
		});
		$this->assertSame([1, 2, 3], $ids->all());

		$found = $c->find('name', 'B');
		$this->assertCount(1, $found);
		$this->assertSame(2, $found->first()->id);
		$this->assertSame(2, $c->where('id', 2)->first()->id);

		$byCb = $c->find(function ($item) {
			return $item->id > 2;
		});
		$this->assertCount(1, $byCb);
	}

	public function testFindArraysAndScalars(): void
	{
		$arrays = new Collection([
			['id' => 1, 'v' => 'x'],
			['id' => 2, 'v' => 'y'],
		]);
		$this->assertSame('y', $arrays->find('id', 2)->first()['v']);

		$scalars = new Collection(['a', 'b', 'a']);
		$this->assertSame(['a', 'a'], $scalars->find('a')->all());
		$this->assertTrue($scalars->contains('a'));
		$this->assertFalse($scalars->contains('z'));
	}

	public function testSortGroupSumFieldValues(): void
	{
		$c = new Collection([
			(object) ['id' => 3, 'group' => 'g1', 'price' => 10],
			(object) ['id' => 1, 'group' => 'g2', 'price' => 5],
			(object) ['id' => 2, 'group' => 'g1', 'price' => 7],
		]);

		$c->sortBy('id');
		$this->assertSame([1, 2, 3], $c->fieldValues('id')->all());

		$this->assertSame(22.0, $c->sum('price'));

		$grouped = $c->groupBy('group');
		$this->assertCount(2, $grouped->get('g1'));

		$simple = new Collection([3, 1, 2]);
		$simple->sort('DESC');
		$this->assertSame([3, 2, 1], $simple->all());
	}

	public function testTransformJoinUniqueChunkEach(): void
	{
		$c = new Collection([1, 2, 2, 3]);
		$c->transform(function ($v) {
			return $v * 2;
		});
		$this->assertSame([2, 4, 4, 6], $c->all());
		$this->assertSame('2-4-4-6', $c->join('-'));

		$unique = (new Collection([1, 1, 2]))->unique();
		$this->assertCount(2, $unique);

		$chunked = new Collection([1, 2, 3, 4]);
		$chunked->chunk(2);
		$this->assertCount(2, $chunked->all());

		$seen = [];
		(new Collection(['x', 'y']))->each(function ($item) use (&$seen) {
			$seen[] = $item;
		});
		$this->assertSame(['x', 'y'], $seen);
	}

	public function testToArrayUsesModelToArray(): void
	{
		$model = new class {
			public function toArray()
			{
				return ['id' => 7];
			}
		};
		$c = new Collection([$model, ['raw' => 1]]);
		$this->assertSame([['id' => 7], ['raw' => 1]], $c->toArray());
	}

	public function testContainsOnObjects(): void
	{
		$c = new Collection([
			(object) ['id' => 1],
			(object) ['id' => 2],
		]);
		$this->assertTrue($c->contains('id', 2));
		$this->assertFalse($c->contains('id', 9));
	}

	public function testFilterRejectsNonCallable(): void
	{
		$this->expectException(\Exception::class);
		(new Collection([1]))->filter('nope');
	}
}
