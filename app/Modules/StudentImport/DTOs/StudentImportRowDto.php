<?php

namespace App\Modules\StudentImport\DTOs;

/**
 * One row from the platform's `students_import.xlsx` template, after the
 * English header names have been mapped to stable internal keys.
 */
final class StudentImportRowDto
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $nationalId,
        public readonly ?string $acceptanceYear,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $fatherName,
        public readonly ?string $grandfatherName,
        public readonly ?string $username,
        public readonly ?string $password,
        public readonly ?string $grade,
        public readonly ?string $classRoom,
        public readonly ?string $gender,
        public readonly ?string $mobile,
        public readonly ?string $email,
        public readonly ?string $birthDate,
        public readonly ?string $birthPlace,
        public readonly ?string $nationality,
        public readonly ?string $passportId,
        public readonly ?string $academicId,
        public readonly ?string $previousSchool,
        public readonly ?string $fingerprintId,
        public readonly ?string $fatherNationalId,
        public readonly ?string $fatherMobile,
        public readonly ?string $motherNationalId,
        public readonly ?string $motherFullName,
        public readonly ?string $motherMobile,
        public readonly ?string $firstNameEn,
        public readonly ?string $fatherNameEn,
        public readonly ?string $grandfatherNameEn,
        public readonly ?string $lastNameEn,
        public readonly ?string $seatNumber,
    ) {}

    /** Composed Arabic full name from the four name parts. */
    public function fullName(): string
    {
        $parts = array_filter([
            $this->firstName,
            $this->fatherName,
            $this->grandfatherName,
            $this->lastName,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');

        return trim(implode(' ', $parts)) ?: ('طالب '.(string) $this->nationalId);
    }

    public static function fromArray(array $a): self
    {
        return new self(
            rowNumber: (int) ($a['rowNumber'] ?? 0),
            nationalId: $a['nationalId'] ?? null,
            acceptanceYear: $a['acceptanceYear'] ?? null,
            firstName: $a['firstName'] ?? null,
            lastName: $a['lastName'] ?? null,
            fatherName: $a['fatherName'] ?? null,
            grandfatherName: $a['grandfatherName'] ?? null,
            username: $a['username'] ?? null,
            password: $a['password'] ?? null,
            grade: $a['grade'] ?? null,
            classRoom: $a['classRoom'] ?? null,
            gender: $a['gender'] ?? null,
            mobile: $a['mobile'] ?? null,
            email: $a['email'] ?? null,
            birthDate: $a['birthDate'] ?? null,
            birthPlace: $a['birthPlace'] ?? null,
            nationality: $a['nationality'] ?? null,
            passportId: $a['passportId'] ?? null,
            academicId: $a['academicId'] ?? null,
            previousSchool: $a['previousSchool'] ?? null,
            fingerprintId: $a['fingerprintId'] ?? null,
            fatherNationalId: $a['fatherNationalId'] ?? null,
            fatherMobile: $a['fatherMobile'] ?? null,
            motherNationalId: $a['motherNationalId'] ?? null,
            motherFullName: $a['motherFullName'] ?? null,
            motherMobile: $a['motherMobile'] ?? null,
            firstNameEn: $a['firstNameEn'] ?? null,
            fatherNameEn: $a['fatherNameEn'] ?? null,
            grandfatherNameEn: $a['grandfatherNameEn'] ?? null,
            lastNameEn: $a['lastNameEn'] ?? null,
            seatNumber: $a['seatNumber'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'rowNumber' => $this->rowNumber,
            'nationalId' => $this->nationalId,
            'acceptanceYear' => $this->acceptanceYear,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fatherName' => $this->fatherName,
            'grandfatherName' => $this->grandfatherName,
            'username' => $this->username,
            'password' => $this->password,
            'grade' => $this->grade,
            'classRoom' => $this->classRoom,
            'gender' => $this->gender,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'birthDate' => $this->birthDate,
            'birthPlace' => $this->birthPlace,
            'nationality' => $this->nationality,
            'passportId' => $this->passportId,
            'academicId' => $this->academicId,
            'previousSchool' => $this->previousSchool,
            'fingerprintId' => $this->fingerprintId,
            'fatherNationalId' => $this->fatherNationalId,
            'fatherMobile' => $this->fatherMobile,
            'motherNationalId' => $this->motherNationalId,
            'motherFullName' => $this->motherFullName,
            'motherMobile' => $this->motherMobile,
            'firstNameEn' => $this->firstNameEn,
            'fatherNameEn' => $this->fatherNameEn,
            'grandfatherNameEn' => $this->grandfatherNameEn,
            'lastNameEn' => $this->lastNameEn,
            'seatNumber' => $this->seatNumber,
        ];
    }
}
