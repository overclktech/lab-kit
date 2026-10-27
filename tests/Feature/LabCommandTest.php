<?php

it('rejects input that is not json', function (): void {
    expect(lab('lab:users', '{not json'))->toBe([1, ['ok' => false, 'error' => ['code' => 'invalid_json', 'message' => 'A entrada não é um JSON válido.']]]);
});

it('rejects input above the size limit', function (): void {
    [$exitCode, $response] = lab('lab:users', str_repeat(' ', 65537));

    expect($exitCode)->toBe(1)->and($response['error']['code'])->toBe('input_too_large');
});

it('rejects a payload without operator', function (): void {
    [$exitCode, $response] = lab('lab:users', ['operation_id' => 'op-1', 'params' => []]);

    expect($exitCode)->toBe(1)->and($response['error']['code'])->toBe('invalid_input');
});

it('rejects unknown keys in the payload and in the params', function (array $payload): void {
    [$exitCode, $response] = lab('lab:users', $payload);

    expect($exitCode)->toBe(1)->and($response['error']['code'])->toBe('invalid_input');
})->with([
    'payload' => [[...payload(), 'command' => 'rm -rf /']],
    'params' => [payload(['order_by' => 'password'])],
]);
