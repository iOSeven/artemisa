<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('peoples', function (Blueprint $table) {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->string('paterno');
            $table->string('materno')->nullable();
            $table->string('fuente')->nullable();
            $table->string('rfc');
            $table->string('curp')->nullable();
            $table->string('nss')->nullable();
            $table->string('correoempresa')->nullable();
            $table->string('correopersonal')->nullable();
            $table->date('nacimiento')->nullable();
            $table->string('sexo')->nullable();
            $table->string('civil')->nullable();
            $table->string('telefono')->nullable();
            $table->string('extension')->nullable();
            $table->string('celular')->nullable();
            $table->date('ingreso')->nullable();
            $table->date('fechapuesto')->nullable();
            $table->string('jefe')->nullable();
            $table->string('turno')->nullable();
            $table->string('tiponomina')->nullable();
            $table->string('clavenomina')->nullable();
            $table->string('nombrenomina')->nullable();
            $table->string('relacion')->nullable();
            $table->string('contrato')->nullable();
            $table->string('horario')->nullable();
            $table->string('jornada')->nullable();
            $table->string('calculo')->nullable();
            $table->string('vacaciones')->nullable();
            $table->string('flotante')->nullable();
            $table->string('base')->nullable();
            $table->string('rol')->nullable();
            $table->string('extra1')->nullable();
            $table->string('extra2')->nullable();
            $table->string('extra3')->nullable();
            $table->string('extra4')->nullable();
            $table->string('extra5')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    //CONSIDERAR UNA MEJOR FORMA
        //$table->string('direccion')->nullable();
        //$table->string('departamento')->nullable();
        //$table->string('seccion')->nullable();
        //$table->integer('job_position_id')->unsigned()->nullable();
        //$table->foreign('job_position_id')->references('id')->on('job_positions');
        //$table->string('puesto')->nullable();
        //$table->string('grado')->nullable();
        //$table->string('region')->nullable();
        //$table->string('sucursal')->nullable();
        //$table->integer('enterprise_id')->unsigned()->nullable();
        //$table->foreign('enterprise_id')->references('id')->on('enterprises');
        //$table->integer('idempresa')->nullable();
        //$table->string('empresa')->nullable();
        //$table->string('division')->nullable();
        //$table->string('marca')->nullable();
        //$table->string('centro')->nullable();  
        //$table->string('checador')->nullable();
        //$table->string('generalista')->nullable();
        //$table->string('password')->nullable();
     
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('peoples');
    }
}
