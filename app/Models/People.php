<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class People extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'peoples';

    protected $fillable = [
        'clave',
        'nombre',
        'paterno',
        'materno',
        'fuente',
        'rfc',
        'curp',
        'nss',
        'correoempresa',
        'correopersonal',
        'nacimiento',
        'sexo',
        'civil',
        'telefono',
        'extension',
        'celular',
        'ingreso',
        'fechapuesto',
        'jefe',
        //'direccion',
        //'departamento',
        //'seccion',
        //'puesto',
        //'grado',
        //'region',
        //'sucursal',
        //'idempresa',
        //'empresa',
        //'division',
        //'marca',
        //'centro',  
        //'checador',
        'turno',
        'tiponomina',
        'clavenomina',
        'nombrenomina',
        //'generalista',
        'relacion',
        'contrato',
        'horario',
        'jornada',
        'calculo',
        'vacaciones',
        'flotante',
        'base',
        'rol',
        //'password',
        'extra1',
        'extra2',
        'extra3',
        'extra4',
        'extra5',
        //'fecha',
        'version',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function user(){
        return $this->hasOne(User::class, 'people_id');
    }
}
