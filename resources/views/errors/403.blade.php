@extends('ig-common::errors.minimal')

@section('title', __('ig-common::errors.403'))
@section('code', '403')
@section('message', $exception->getMessage() ?: __('ig-common::errors.403_message'))
