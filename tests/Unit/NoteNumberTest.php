<?php

use App\Support\NoteNumber;

it('rejeita nota vazia, nula ou só zeros', function (mixed $value) {
    expect(NoteNumber::normalize($value))->toBeNull()
        ->and(NoteNumber::isValid($value))->toBeFalse();
})->with([null, '', '   ', '0', 0, '000', ' 0 ', 'NULL', 'null']);

it('mantém nota válida sem espaços nas pontas', function () {
    expect(NoteNumber::normalize(' 73196741 '))->toBe('73196741')
        ->and(NoteNumber::normalize('1000'))->toBe('1000')
        ->and(NoteNumber::normalize('A0'))->toBe('A0');
});
