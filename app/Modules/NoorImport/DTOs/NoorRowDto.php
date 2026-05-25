<?php

namespace App\Modules\NoorImport\DTOs;

/**
 * One row coming out of a Noor Excel/CSV file, after we have mapped
 * the Arabic Noor header names to a stable internal key set.
 */
final class NoorRowDto
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $nationalId,
        public readonly ?string $academicNumber,
        public readonly ?string $name,
        public readonly ?string $gender,
        public readonly ?string $birthDate,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $grade = null,
        public readonly ?string $classRoom = null,
        public readonly ?string $specialization = null,
        public readonly ?string $nationality = null,
        public readonly ?string $studentStatus = null,
        public readonly ?string $parentName = null,
        public readonly ?string $parentNationalId = null,
        public readonly ?string $parentPhone = null,
        public readonly array $raw = [],
    ) {}

    /** Rebuild a DTO from the JSON we persisted at preview time. */
    public static function fromArray(array $a): self
    {
        return new self(
            rowNumber: (int) ($a['rowNumber'] ?? 0),
            nationalId: $a['nationalId'] ?? null,
            academicNumber: $a['academicNumber'] ?? null,
            name: $a['name'] ?? null,
            gender: $a['gender'] ?? null,
            birthDate: $a['birthDate'] ?? null,
            phone: $a['phone'] ?? null,
            email: $a['email'] ?? null,
            grade: $a['grade'] ?? null,
            classRoom: $a['classRoom'] ?? null,
            specialization: $a['specialization'] ?? null,
            nationality: $a['nationality'] ?? null,
            studentStatus: $a['studentStatus'] ?? null,
            parentName: $a['parentName'] ?? null,
            parentNationalId: $a['parentNationalId'] ?? null,
            parentPhone: $a['parentPhone'] ?? null,
            raw: $a['raw'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'rowNumber'        => $this->rowNumber,
            'nationalId'       => $this->nationalId,
            'academicNumber'   => $this->academicNumber,
            'name'             => $this->name,
            'gender'           => $this->gender,
            'birthDate'        => $this->birthDate,
            'phone'            => $this->phone,
            'email'            => $this->email,
            'grade'            => $this->grade,
            'classRoom'        => $this->classRoom,
            'specialization'   => $this->specialization,
            'nationality'      => $this->nationality,
            'studentStatus'    => $this->studentStatus,
            'parentName'       => $this->parentName,
            'parentNationalId' => $this->parentNationalId,
            'parentPhone'      => $this->parentPhone,
        ];
    }
}
