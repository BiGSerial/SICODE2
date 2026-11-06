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

it('ignora espaço não separável e caracteres invisíveis na nota', function () {
    expect(NoteNumber::normalize("0003182898\u{200B}"))->toBe('0003182898')
        ->and(NoteNumber::key("\u{00A0}0003182898\u{FEFF}"))->toBe('3182898')
        ->and(NoteNumber::variants("0003182898\u{200B}"))->toBe(['0003182898', '3182898'])
        ->and(NoteNumber::normalize("\u{200B}"))->toBeNull();
});

it('mantém as grafias com e sem zeros ao juntar várias notas (unique frouxo as fundiria)', function () {
    $variants = array_unique(collect(['0003182898', '73196741'])->flatMap(fn ($v) => NoteNumber::variants($v))->all(), SORT_STRING);

    expect(array_values($variants))->toBe(['0003182898', '3182898', '73196741', '0073196741']);
});
