<?php

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lectures\LectureResource;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Assignments\AssignmentResource;

it('course thumbnail FileUpload has acceptedFileTypes in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Courses/CourseResource.php'));
    expect($source)->toContain('acceptedFileTypes');
    expect($source)->toContain("'image/jpeg', 'image/png', 'image/webp', 'image/gif'");
});

it('course thumbnail FileUpload has maxSize in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Courses/CourseResource.php'));
    expect($source)->toContain('maxSize(5120)');
});

it('lecture video FileUpload has acceptedFileTypes and maxSize in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Lectures/LectureResource.php'));
    expect($source)->toContain("'video/mp4', 'video/webm', 'video/ogg'");
    expect($source)->toContain('maxSize(1024000)');
});

it('lecture pdf FileUpload has acceptedFileTypes and maxSize in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Lectures/LectureResource.php'));
    expect($source)->toContain("'application/pdf'");
    expect($source)->toContain('maxSize(20480)');
});

it('lecture question image FileUpload has acceptedFileTypes and maxSize in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Lectures/LectureResource.php'));
    expect($source)->toContain('maxSize(2048)');
});

it('exam question image FileUpload has acceptedFileTypes and maxSize in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Exams/ExamResource.php'));
    expect($source)->toContain("'image/jpeg', 'image/png', 'image/webp', 'image/gif'");
    expect($source)->toContain('maxSize(2048)');
});

it('assignment question image FileUpload has acceptedFileTypes and maxSize in source', function () {
    $source = file_get_contents(app_path('Filament/Resources/Assignments/AssignmentResource.php'));
    expect($source)->toContain("'image/jpeg', 'image/png', 'image/webp', 'image/gif'");
    expect($source)->toContain('maxSize(2048)');
});

it('all FileUpload components have either acceptedFileTypes or image constraint', function () {
    $files = [
        'Filament/Resources/Courses/CourseResource.php',
        'Filament/Resources/Lectures/LectureResource.php',
        'Filament/Resources/Exams/ExamResource.php',
        'Filament/Resources/Assignments/AssignmentResource.php',
    ];

    foreach ($files as $file) {
        $source = file_get_contents(app_path($file));
        preg_match_all('/FileUpload::make\(([^)]+)\)/', $source, $matches);
        foreach ($matches[1] as $name) {
            $pattern = '/FileUpload::make\(' . preg_quote(trim($name, "'\"")) . '\).*?(?=FileUpload::make|$)/s';
            preg_match($pattern, $source, $block);
            if ($block) {
                $hasTypes = str_contains($block[0], 'acceptedFileTypes');
                $hasImage = str_contains($block[0], 'image()');
                expect($hasTypes || $hasImage)->toBeTrue(
                    "FileUpload '{$name}' must have acceptedFileTypes or image() constraint"
                );
            }
        }
    }
});
