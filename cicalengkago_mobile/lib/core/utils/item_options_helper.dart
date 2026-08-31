import 'dart:convert';

/// Helper utility to consistently extract and format product variations, addons/toppings,
/// and special notes across Customer, Driver, and Merchant transaction interfaces.
class ItemOptionsHelper {
  /// Extracts the product variation name (e.g. "Pedas Level 3", "Ukuran Large", "Coklat Keju")
  static String? getVariationName(dynamic item) {
    if (item == null || item is! Map) return null;

    // 1. Direct string keys
    final directKeys = ['variation_name', 'variant_name', 'variant', 'selected_variation_name'];
    for (final k in directKeys) {
      if (item[k] != null && item[k].toString().trim().isNotEmpty) {
        final val = item[k].toString().trim();
        if (val != '{}' && val != '[]' && val != 'null') {
          return val;
        }
      }
    }

    // 2. variation_json or variations_json field
    final jsonKeys = ['variation_json', 'variations_json', 'variation', 'variations'];
    for (final k in jsonKeys) {
      final raw = item[k];
      if (raw == null) continue;

      if (raw is Map) {
        final name = raw['name'] ?? raw['variation_name'] ?? raw['title'] ?? raw['label'];
        if (name != null && name.toString().trim().isNotEmpty) {
          return name.toString().trim();
        }
      } else if (raw is List && raw.isNotEmpty) {
        final first = raw.first;
        if (first is Map) {
          final name = first['name'] ?? first['variation_name'] ?? first['title'];
          if (name != null && name.toString().trim().isNotEmpty) {
            return name.toString().trim();
          }
        } else if (first is String && first.trim().isNotEmpty) {
          return first.trim();
        }
      } else if (raw is String && raw.trim().isNotEmpty) {
        final trimmed = raw.trim();
        if (trimmed == '{}' || trimmed == '[]' || trimmed == 'null') continue;

        try {
          final decoded = jsonDecode(trimmed);
          if (decoded is Map) {
            final name = decoded['name'] ?? decoded['variation_name'] ?? decoded['title'] ?? decoded['label'];
            if (name != null && name.toString().trim().isNotEmpty) {
              return name.toString().trim();
            }
          } else if (decoded is List && decoded.isNotEmpty) {
            final first = decoded.first;
            if (first is Map) {
              final name = first['name'] ?? first['variation_name'] ?? first['title'];
              if (name != null && name.toString().trim().isNotEmpty) {
                return name.toString().trim();
              }
            } else if (first is String && first.trim().isNotEmpty) {
              return first.trim();
            }
          }
        } catch (_) {
          // If not JSON, but a clean text string, use it
          if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
            return trimmed;
          }
        }
      }
    }

    return null;
  }

  /// Extracts a list of addon/topping names (e.g. ["Extra Keju", "Telur Mata Sapi"])
  static List<String> getAddonNames(dynamic item) {
    if (item == null || item is! Map) return [];

    final List<String> result = [];

    // 1. Direct addons list
    if (item['addons'] is List) {
      for (final ad in item['addons']) {
        if (ad is Map && ad['name'] != null && ad['name'].toString().trim().isNotEmpty) {
          result.add(ad['name'].toString().trim());
        } else if (ad is String && ad.trim().isNotEmpty) {
          result.add(ad.trim());
        }
      }
      if (result.isNotEmpty) return result;
    }

    // 2. addons_json field
    final rawJson = item['addons_json'] ?? item['addons_text'];
    if (rawJson != null) {
      if (rawJson is List) {
        for (final ad in rawJson) {
          if (ad is Map && ad['name'] != null) {
            result.add(ad['name'].toString().trim());
          } else if (ad is String && ad.trim().isNotEmpty) {
            result.add(ad.trim());
          }
        }
      } else if (rawJson is Map) {
        if (rawJson['items'] is List) {
          for (final ad in rawJson['items']) {
            if (ad is Map && ad['name'] != null) {
              result.add(ad['name'].toString().trim());
            } else if (ad is String && ad.trim().isNotEmpty) {
              result.add(ad.trim());
            }
          }
        }
      } else if (rawJson is String && rawJson.trim().isNotEmpty) {
        final trimmed = rawJson.trim();
        if (trimmed != '{}' && trimmed != '[]' && trimmed != 'null') {
          try {
            final decoded = jsonDecode(trimmed);
            if (decoded is List) {
              for (final ad in decoded) {
                if (ad is Map && ad['name'] != null) {
                  result.add(ad['name'].toString().trim());
                } else if (ad is String && ad.trim().isNotEmpty) {
                  result.add(ad.trim());
                }
              }
            } else if (decoded is Map && decoded['items'] is List) {
              for (final ad in decoded['items']) {
                if (ad is Map && ad['name'] != null) {
                  result.add(ad['name'].toString().trim());
                } else if (ad is String && ad.trim().isNotEmpty) {
                  result.add(ad.trim());
                }
              }
            }
          } catch (_) {
            // Comma separated text fallback
            if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
              final splits = trimmed.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty);
              result.addAll(splits);
            }
          }
        }
      }
    }

    return result;
  }

  /// Extracts special instruction notes for the item
  static String? getItemNotes(dynamic item) {
    if (item == null || item is! Map) return null;
    final keys = ['item_notes', 'notes', 'order_notes', 'catatan', 'note'];
    for (final k in keys) {
      if (item[k] != null && item[k].toString().trim().isNotEmpty) {
        final val = item[k].toString().trim();
        if (val != 'null' && val != '-') return val;
      }
    }
    return null;
  }
}
