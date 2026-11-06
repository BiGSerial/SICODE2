<?php

use App\Models\Note;

it('impede o model Note de gravar número inválido', function (mixed $value) {
    expect(fn () => (new Note(['note' => $value]))->save())
        ->toThrow(InvalidArgumentException::class);
})->with([null, '', '0', '000']);
