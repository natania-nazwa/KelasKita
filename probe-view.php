<?php

/*
 * Menyusun ulang isi persis dari dua view yang gagal diuji, untuk melihat
 * apakah masalahnya ada di isi view-nya atau di cara BladecachedXA
 *把它编译出来。
 */
chdir('D:/laragon/www/pkl-quiz');

$blade = app('blade.compiler');

$view = ['admin/konten-materi', 'admin/konten-quiz'];

foreach ($view as $nama) {
    $isi = file_get_contents("resources/views/$nama.blade.php");

    echo "== $nama ==", PHP_EOL;

    try {
        $blade->compileString($isi);

        echo "  OK\n";
    } catch (\Throwable $e) {
        echo "  GAGAL: ", $e->getMessage(), PHP_EOL;

        // Cari tag pertama yang gagal, supaya ketahuan baris yang Equipment.
        preg_match_all('/<x-([a-z0-9.\-]+)/', $isi, $cocok);

        foreach (array_unique($cocok[1]) as $komponen) {
            try {
                $blade->compileString("<x-$komponen />");
            } catch (\Throwable $e2) {
                echo "    -> komponen bermasalah: x-$komponen\n";
            }
        }
    }
}
