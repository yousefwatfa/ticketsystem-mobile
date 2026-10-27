import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';
import * as ImagePicker from 'expo-image-picker';
import { Platform } from 'react-native';

/** Server limit is 2 MB per file; photos are resized to 1600px and saved as JPEG. */
const MAX_DIMENSION = 1600;

export type PickedFile = {
  uri: string;
  name: string;
  type: string;
  width?: number;
  height?: number;
};

async function compress(asset: ImagePicker.ImagePickerAsset, index: number): Promise<PickedFile> {
  const longest = Math.max(asset.width || 0, asset.height || 0);
  const context = ImageManipulator.manipulate(asset.uri);
  if (longest > MAX_DIMENSION) {
    context.resize(asset.width >= asset.height ? { width: MAX_DIMENSION, height: null } : { width: null, height: MAX_DIMENSION });
  }
  const rendered = await context.renderAsync();
  const result = await rendered.saveAsync({ format: SaveFormat.JPEG, compress: 0.75 });

  return {
    uri: result.uri,
    name: `photo-${Date.now()}-${index}.jpg`,
    type: 'image/jpeg',
    width: result.width,
    height: result.height,
  };
}

/** Opens the camera (or gallery) and returns compressed photos, or [] when cancelled. */
export async function pickPhotos(source: 'camera' | 'library'): Promise<PickedFile[]> {
  if (source === 'camera') {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted && Platform.OS !== 'web') {
      throw new Error('يرجى السماح باستخدام الكاميرا من إعدادات الهاتف.');
    }
  }

  const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 1 };
  const result =
    source === 'camera'
      ? await ImagePicker.launchCameraAsync(options)
      : await ImagePicker.launchImageLibraryAsync({ ...options, allowsMultipleSelection: true, selectionLimit: 5 });

  if (result.canceled || !result.assets) return [];

  return Promise.all(result.assets.map((asset, index) => compress(asset, index)));
}

/** Appends a picked file to multipart FormData (web needs a Blob, native accepts {uri,name,type}). */
export async function appendFile(form: FormData, field: string, file: PickedFile) {
  if (Platform.OS === 'web') {
    const blob = await (await fetch(file.uri)).blob();
    form.append(field, blob, file.name);
  } else {
    form.append(field, { uri: file.uri, name: file.name, type: file.type } as unknown as Blob);
  }
}
