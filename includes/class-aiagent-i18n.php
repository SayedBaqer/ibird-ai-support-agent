<?php
defined( 'ABSPATH' ) || exit;

class AIAgent_I18n {

	// Detect language from text using a basic heuristic (Arabic Unicode range).
	public static function detect( string $text ): string {
		if ( preg_match( '/[\x{0600}-\x{06FF}]/u', $text ) ) {
			return 'ar';
		}
		return 'en';
	}

	// Merge detected language with user preference (prefer what the customer typed).
	public static function resolve( string $message, string $stored_lang = 'en' ): string {
		$detected = self::detect( $message );
		return $detected !== 'en' ? $detected : $stored_lang;
	}

	public static function is_rtl( string $lang ): bool {
		return $lang === 'ar';
	}

	// Return a localised string from plugin settings (fallback_message_en / fallback_message_ar).
	public static function setting_string( string $key, string $lang ): string {
		$settings = aiagent_settings();
		$lang_key = $key . '_' . $lang;
		if ( isset( $settings[ $lang_key ] ) && $settings[ $lang_key ] !== '' ) {
			return $settings[ $lang_key ];
		}
		// Fallback to English.
		$en_key = $key . '_en';
		return $settings[ $en_key ] ?? '';
	}
}
