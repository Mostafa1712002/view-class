<?php

namespace Tests\Unit\SpecialPrograms;

use App\Modules\SpecialPrograms\Actions\Import\ImportAlAwwalBank;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** Image paths come from the source DB: they must never read outside --images nor write odd names into public storage. */
class ImportImagePathTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/sp-img-'.uniqid();
        mkdir($this->root.'/images/uploads/questions', 0777, true);
        file_put_contents($this->root.'/images/uploads/questions/q1.PNG', 'png-bytes');
        file_put_contents($this->root.'/secret.png', 'outside');
        file_put_contents($this->root.'/images/uploads/questions/evil.php', '<?php');
        symlink($this->root.'/secret.png', $this->root.'/images/uploads/questions/link.png');
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    private function inspect(string $path): array
    {
        $import = (new ReflectionClass(ImportAlAwwalBank::class))->newInstanceWithoutConstructor();
        $targets = [];

        return (fn () => $this->inspectImage($path, func_get_arg(0), 'qiyas', $targets))->call($import, $this->root.'/images');
    }

    public function test_normal_image_maps_to_program_folder_with_lowercase_ext(): void
    {
        $r = $this->inspect('uploads/questions/q1.PNG');
        $this->assertTrue($r['exists']);
        $this->assertSame('special-programs/qiyas/q1.png', $r['target']);
    }

    public function test_traversal_and_symlink_outside_images_are_refused(): void
    {
        foreach (['../secret.png', 'uploads/questions/link.png'] as $path) {
            try {
                $this->inspect($path);
                $this->fail("accepted {$path}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('outside the images directory', $e->getMessage());
            }
        }
    }

    public function test_non_image_extension_is_refused(): void
    {
        $this->expectExceptionMessage('extension not allowed');
        $this->inspect('uploads/questions/evil.php');
    }
}
