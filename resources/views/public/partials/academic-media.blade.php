@php
    $academicMedia = [
        'Kegiatan Pembelajaran' => $learningActivities,
        'Kalender Pendidikan' => $educationCalendar,
        'Program Kokurikuler dan Ekstrakurikuler' => $academicPrograms,
        'Informasi Asesmen Akademik' => $academicAssessments,
    ];
    $academicMedia = collect($academicMedia)->map(fn ($entries) => $entries->map(fn ($entry) => [
        'image' => $entry->image_path ? Storage::disk('public')->url($entry->image_path) : null,
        'alt' => $entry->metadata['alt'] ?? $entry->title,
        'description' => trim(strip_tags($entry->body)),
    ])->values());
@endphp
<script>window.academicEntryMedia=@json($academicMedia);</script>
