<?php

declare(strict_types=1);

namespace PSR7SessionEncodeDecode;

final class Decoder implements DecoderInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(string $encodedSessionData): array
    {
        if ('' === $encodedSessionData) {
            return [];
        }

        $serializeTypes = [
            'N',                  // null
            'b\:[0-1]',           // boolean
            'a\:[0-9]+\:\{',      // array
            's\:[0-9]+\:"',       // string
            'd\:[0-9]+\.?[0-9]*', // float
            'i\:[0-9]+',          // integer/resource
            'O\:[0-9]+\:"',       // object
            // We can't support references even though PHP's native session encoder does
            // since we deserialize each key in a separate step.
            // 'r\:[0-9]+',        // resource
        ];

        $serializeTypesString = '(?:' . implode('|', $serializeTypes) . ')';

        preg_match_all('/(?:^|;|\})(\w+)\|' . $serializeTypesString . '/', $encodedSessionData, $matchesarray, PREG_OFFSET_CAPTURE);

        $decodedData = [];

        $lastOffset = null;
        $currentKey = '';
        foreach ($matchesarray[1] as $value) {
            $offset = $value[1];
            if (null !== $lastOffset) {
                $valueText = substr($encodedSessionData, $lastOffset, $offset - $lastOffset);

                /** @noinspection UnserializeExploitsInspection */
                $decodedData[$currentKey] = unserialize($valueText);
            }
            $currentKey = $value[0];

            $lastOffset = $offset + strlen($currentKey) + 1;
        }

        $valueText = substr($encodedSessionData, $lastOffset);

        /** @noinspection UnserializeExploitsInspection */
        $decodedData[$currentKey] = unserialize($valueText);

        return $decodedData;
    }
}
