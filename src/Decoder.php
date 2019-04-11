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

        $serializeTypes = [];
        // array
        $serializeTypes[]  = 'a\:[0-9]+\:\{';
        // string
        $serializeTypes[]  = 's\:[0-9]+\:"';
        // float
        $serializeTypes[]  = 'd\:[0-9]+\.?[0-9]*';
        // integer/resource
        $serializeTypes[]  = 'i\:[0-9]+';
        // null
        $serializeTypes[] = 'N';
        // boolean
        $serializeTypes[] = 'b\:[0-1]';
        // object O:<i>:"
        $serializeTypes[] = 'O\:[0-9]+\:"';
        // We can't support references even though PHP's native session encoder does
        // since we deserialize each key in a separate step.
        // $serializeTypes[] = 'r\:[0-9]+';

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
