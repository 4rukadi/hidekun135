const readline = require('readline');
const axios = require('axios');
const fs = require('fs').promises;
const crypto = require('crypto');

function base64URLEncode(str) {
  return str.toString('base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=/g, '');
}

// Fungsi untuk menghitung SHA256 hash
function sha256(buffer) {
  return crypto.createHash('sha256').update(buffer).digest();
}

const getQueryParam = (url, param) => {
  const queryParams = new URLSearchParams(new URL(url).search);
  return queryParams.get(param);
};
// Fungsi-fungsi terkait PKCE (Proof Key for Code Exchange)
const generatePKCE = () => {
  const verifier = base64URLEncode(crypto.randomBytes(64));
  const challenge = base64URLEncode(sha256(verifier));
  return { verifier, challenge };
};

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

let userPhoneNumber;
let authIdFirstRequest;
let authIdSecondRequest;
let authIdThirdRequest;
let savedVerifier;
let idToken;
let accessToken;


const saveRequestToFile = async (fileName, requestData) => {
  try {
    await fs.writeFile(fileName, JSON.stringify(requestData, null, 2));
    console.log(`Request berhasil disimpan dalam ${fileName}`);
  } catch (error) {
    console.error(`Error saat menyimpan request dalam ${fileName}:`, error);
  }
};

const performZeroRequest = async () => {
  try {
    const zeroRequestConfig = {
      method: 'POST',
      url: 'https://api.myxl.xlaxiata.co.id/infos/api/v1/registration/prepaid',
      headers: {
        'Host': 'api.myxl.xlaxiata.co.id',
        'X-Dynatrace': 'MT_3_5_3856414540_2-0_24d94a15-af8c-49e7-96a0-1ddb48909564_0_505_57',
        'X-Api-Key': 'vT8tINqHaOxXbGE7eOWAhA==',
        'X-Request-Id': '45a55a8f-ceb8-405b-883e-4e1f8919bb65',
        'X-Request-At': '2023-11-27T20:06:43.05+07:00',
        'X-Version-App': '5.8.5',
        'User-Agent': 'myXL / 5.8.5(698); android; (samsung; Nexus; SDK 29; Android 10)',
        'Content-Type': 'application/json; charset=utf-8',
        'Accept-Encoding': 'gzip, deflate, br'
      },
      data: {
        'lang': 'en',
        'is_enterprise': false,
        'msisdn': userPhoneNumber // Gunakan nomor telepon yang sudah diinput sebelumnya
      }
    };

    const zeroResponse = await axios(zeroRequestConfig);
    await saveRequestToFile('zero.txt', zeroRequestConfig); // Simpan permintaan ke Zero
    await saveRequestToFile('respons_zero.txt', zeroResponse.data); // Simpan respons dari Zero

    console.log('Permintaan ke Zero selesai.');
  } catch (error) {
    console.error('Error saat melakukan permintaan ke Zero:', error);
  }
};

const performRequests = async () => {
  try {
    await performZeroRequest();
    const firstRequestConfig = {
      method: 'post',
      url: 'https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate?authIndexType=service&authIndexValue=otp',
      headers: {
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Accept-Encoding': 'gzip, deflate, br',
        'User-Agent': 'okhttp/4.3.1',
        'Connection': 'close'
      }
    };

    const response = await axios(firstRequestConfig);
    authIdFirstRequest = response.data.authId;
    await saveRequestToFile('pertama.txt', firstRequestConfig); // Simpan permintaan pertama
    await saveRequestToFile('respons_pertama.txt', response.data); // Simpan respons pertama
    const cookie = response.headers['set-cookie'];

    const secondRequestConfig = {
      method: 'post',
      url: 'https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
      headers: {
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Content-Type': 'application/json; charset=utf-8',
        'Accept-Encoding': 'gzip, deflate, br',
        'User-Agent': 'okhttp/4.3.1',
        'Cookie': cookie,
      },
      data: {
        authId: authIdFirstRequest,
        stage: 'MSISDN',
        callbacks: [
          {
            type: 'MetadataCallback',
            output: [{ name: 'data', value: { stage: 'MSISDN' } }],
            _id: 0
          },
          {
            type: 'NameCallback',
            output: [{ name: 'prompt', value: 'MSISDN' }],
            input: [{ name: 'IDToken2', value: userPhoneNumber }],
            _id: 1
          },
          {
            type: 'HiddenValueCallback',
            output: [
              { name: 'value', value: '' },
              { name: 'id', value: 'Language' }
            ],
            input: [{ name: 'IDToken3', value: 'MYXLU_AND_LOGIN_EN' }],
            _id: 2
          }
        ]
      }
    };

    const secondResponse = await axios(secondRequestConfig);
    authIdSecondRequest = secondResponse.data.authId;
    await saveRequestToFile('kedua.txt', secondRequestConfig); // Simpan permintaan kedua
    await saveRequestToFile('respons_kedua.txt', secondResponse.data); // Simpan respons kedua
    const secondCookie = secondResponse.headers['set-cookie'];

    const thirdRequestConfig = {
      method: 'post',
      url: 'https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
      headers: {
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Content-Type': 'application/json; charset=utf-8',
        'Accept-Encoding': 'gzip, deflate, br',
        'User-Agent': 'okhttp/4.3.1',
        'Cookie': secondCookie,
      },
      data: {
        authId: authIdSecondRequest,
        stage: 'DEVICE',
        callbacks: [
          {
            type: 'MetadataCallback',
            output: [{ name: 'data', value: { stage: 'DEVICE' } }],
            _id: 4
          },
          {
            type: 'HiddenValueCallback',
            output: [
              { name: 'value', value: 'Input Device Information' },
              { name: 'id', value: 'DeviceInformation' }
            ],
            input: [
              { name: 'IDToken2', value: '6de32550ceb17f47-47a0a4d613edafd29f4c99e683ce09a37d96edaa' }
            ],
            _id: 5
          }
        ]
      }
    };

    const thirdResponse = await axios(thirdRequestConfig);
    authIdThirdRequest = thirdResponse.data.authId;
    const responseData3 = thirdResponse.data;
    await saveRequestToFile('ketiga.txt', thirdRequestConfig); // Simpan permintaan ketiga
    await saveRequestToFile('respons_ketiga.txt', thirdResponse.data); // Simpan respons ketiga
    const thirdCookie = thirdResponse.headers['set-cookie'];
    let messageValue;
    if (responseData3.callbacks && responseData3.callbacks.length > 0) {
      const textOutputCallback = responseData3.callbacks.find(callback => callback.type === 'TextOutputCallback');
      if (textOutputCallback && textOutputCallback.output && textOutputCallback.output.length > 0) {
        const messageOutput = textOutputCallback.output.find(output => output.name === 'message');
        if (messageOutput) {
          messageValue = messageOutput.value;
        }
      }
    }

    const fourthRequestConfig = {
      method: 'post',
      url: 'https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
      headers: {
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Content-Type': 'application/json; charset=utf-8',
        'Accept-Encoding': 'gzip, deflate, br',
        'User-Agent': 'okhttp/4.3.1',
        'Cookie': thirdCookie,
      },
      data: {
        authId: authIdThirdRequest,
        stage: 'VALIDATE',
        callbacks: [
          {
            type: 'MetadataCallback',
            output: [{ name: 'data', value: { stage: 'VALIDATE' } }],
            _id: 7
          },
          {
            type: 'ConfirmationCallback',
            output: [
              {
                name: 'prompt',
                value: 'Validate'
              },
              {
                name: 'messageType',
                value: 0
              },
              {
                name: 'options',
                value: ['0 = NO', '1 = YES']
              },
              {
                name: 'optionType',
                value: -1
              },
              {
                name: 'defaultOption',
                value: 0
              }
            ],
            input: [
              {
                name: 'IDToken2',
                value: 1
              }
            ],
            _id: 8
          },
          {
            type: 'TextOutputCallback',
            output: [
              {
                name: 'message',
                value: messageValue,
              },
              {
                name: 'messageType',
                value: '0'
              }
            ],
            _id: 9
          }
        ]
      }
    };

    const fourthResponse = await axios(fourthRequestConfig);
    authIdfourthRequest = fourthResponse.data.authId;
    await saveRequestToFile('keempat.txt', fourthRequestConfig); // Simpan permintaan keempat
    await saveRequestToFile('respons_keempat.txt', fourthResponse.data); // Simpan respons keempat
    const fourthCookie = fourthResponse.headers['set-cookie'];

    // Meminta pengguna memasukkan kode OTP
    rl.question('Masukkan kode OTP: ', async (otp) => {
      const otpValue = otp.trim();
      console.log(`Kode OTP yang dimasukkan: ${otpValue}`);

      await performFifthRequest(otpValue);

      rl.close(); // Menutup interface setelah pengguna memasukkan kode OTP
    });
  } catch (error) {
    console.error('Error saat melakukan permintaan keempat:', error);
  }
};

const performFifthRequest = async (otpValue, fourthCookie, authIdFourthRequest) => {
  try {
    const fifthRequestConfig = {
      method: 'post',
      url: 'https://ciam-rajaampat.xl.co.id/am/json/realms/xl/authenticate',
      headers: {
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Content-Type': 'application/json; charset=utf-8',
        'Cookie': fourthCookie, // Pastikan variabel thirdCookie sudah didefinisikan sebelumnya
      },
      data: {
        authId: authIdfourthRequest, // Pastikan variabel authIdThirdRequest sudah didefinisikan sebelumnya
        stage: 'OTP',
        callbacks: [
          {
            type: 'MetadataCallback',
            output: [
              { name: 'data', value: { stage: 'OTP' } }
            ],
            _id: 0
          },
          {
            type: 'PasswordCallback',
            output: [
              { name: 'prompt', value: 'One Time Password' }
            ],
            input: [
              { name: 'IDToken2', value: otpValue }
            ],
            _id: 1
          },
          {
            type: 'TextOutputCallback',
            output: [
              {
                name: 'message',
                value: '{"code":"000","data":{"max_validation_attempt_suspend_duration":"900","max_validation_attempt":5,"sent_to":"SMS","next_resend_allowed_at":"1700384355"},"status":"SUCCESS"}'
              },
              {
                name: 'messageType',
                value: '0'
              }
            ],
            _id: 2
          },
          {
            type: 'ConfirmationCallback',
            output: [
              { name: 'prompt', value: '' },
              { name: 'messageType', value: 0 },
              { name: 'options', value: ['Submit OTP', 'Request OTP'] },
              { name: 'optionType', value: -1 },
              { name: 'defaultOption', value: 0 }
            ],
            input: [
              { name: 'IDToken4', value: 0 }
            ],
            _id: 3
          }
        ]
      }
    };

    const fifthResponse = await axios(fifthRequestConfig);
    await saveRequestToFile('kelima.txt', fifthRequestConfig); // Simpan permintaan kelima
    await saveRequestToFile('respons_kelima.txt', fifthResponse.data); // Simpan respons kelima
    const fifthCookie = fifthResponse.headers['set-cookie'];
    // Peroleh nilai dari cookie iPlanetDirectoryPro
    const iPlanetDirectoryCookie = fifthCookie.find(cookie => cookie.startsWith('iPlanetDirectoryPro='));
    if (iPlanetDirectoryCookie) {
      const pathIndex = iPlanetDirectoryCookie.indexOf('; Path=');
      const iPlanetDirectoryValue = pathIndex !== -1 ? iPlanetDirectoryCookie.substring('iPlanetDirectoryPro='.length, pathIndex) : iPlanetDirectoryCookie.substring('iPlanetDirectoryPro='.length);
      await performSixthRequest(iPlanetDirectoryValue); // Panggil fungsi performSixthRequest dengan nilai iPlanetDirectoryValue yang sesuai
    }

    // Lakukan hal lain sesuai kebutuhan aplikasi Anda dengan respons kelima di sini

  } catch (error) {
    console.error('Error saat melakukan permintaan kelima:', error);
  }
};



const performSixthRequest = async (iPlanetDirectoryValue) => {
  try {
    // Generate PKCE
    const { 
      verifier, 
      challenge 
    } = generatePKCE(); // Obtain verifier and challenge values
    savedVerifier = verifier;

    const sixRequestConfig = {
      method: 'GET',
      url: `https://ciam-rajaampat.xl.co.id/am/oauth2/realms/xl/authorize?iPlanetDirectoryPro=${iPlanetDirectoryValue}&client_id=a80c1af52aae62d1166b73796ae5f378&scope=openid%20profile&response_type=code&redirect_uri=https%3A%2F%2Fmy.xl.co.id&code_challenge=${challenge}&code_challenge_method=S256`,
      headers: {
        'Host': 'ciam-rajaampat.xl.co.id',
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Accept-Encoding': 'gzip, deflate, br',
        'User-Agent': 'okhttp/4.3.1',
      },
      redirect: 'manual' // Disable automatic redirection
    };

    const response = await fetch(sixRequestConfig.url, {
      method: 'GET',
      headers: sixRequestConfig.headers,
      redirect: 'manual'
    });

    const responseBody = await response.text(); // Get response body as text

    await saveRequestToFile('keenam.txt', sixRequestConfig); // Save the sixth request configuration
    const responseDetails = {
      status: response.status,
      headers: Object.fromEntries(response.headers),
      body: responseBody
    };

    await fs.writeFile('respons_keenam.json', JSON.stringify(responseDetails, null, 2));


    const locationHeader = response.headers.get('Location');

    await performSeventhRequest(locationHeader);
  } catch (error) {
    console.error(error);
  }
};

const performSeventhRequest = async (locationHeader, iPlanetDirectoryValue) => {
  try {

    const codeValue = getQueryParam(locationHeader, 'code');
    const codeVerifier = savedVerifier;

    // Melakukan permintaan ke tujuh dengan menggunakan nilai 'code' yang diekstrak
    const seventhRequestConfig = {
      method: 'POST',
      url: 'https://ciam-rajaampat.xl.co.id/am/oauth2/realms/xl/access_token',
      headers: {
        'Accept-Api-Version': 'resource=2.1, protocol=1.0',
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept-Encoding': 'gzip, deflate, br',
        'User-Agent': 'okhttp/4.3.1',
      },
      data: `client_id=a80c1af52aae62d1166b73796ae5f378&code=${codeValue}&redirect_uri=https%3A%2F%2Fmy.xl.co.id&grant_type=authorization_code&code_verifier=${codeVerifier}`
    };

    const seventhResponse = await axios(seventhRequestConfig);
    idToken = seventhResponse.data.id_token;
    accessToken = seventhResponse.data.access_token;
    await saveRequestToFile('ketujuh.txt', seventhRequestConfig); // Simpan permintaan ketujuh
    await saveRequestToFile('respons_ketujuh.txt', seventhResponse.data); // Simpan respons ketujuh

    console.log('Permintaan ketujuh selesai.');
    await performEighthRequest(idToken, accessToken); // Call performEighthRequest after getting the tokens
  } catch (error) {
    console.error('Error saat melakukan permintaan ketujuh:', error);
  }
};

const performEighthRequest = async (idToken, accessToken) => {
  try {
    const eighthRequestConfig = {
      method: 'POST',
      url: 'https://api.myxl.xlaxiata.co.id/api/v1/auth/login',
      headers: {
        'Host': 'api.myxl.xlaxiata.co.id',
        'X-Api-Key': 'vT8tINqHaOxXbGE7eOWAhA==',
        'Authorization': `Bearer ${idToken}`,
        'X-Version-App': '5.8.5',
        'User-Agent': 'myXL / 5.8.5(698); android; (samsung; Nexus; SDK 29; Android 10)',
        'Content-Type': 'application/json; charset=utf-8',
        'Accept-Encoding': 'gzip, deflate, br'
      },
      data: {
        lang: 'en',
        is_enterprise: false,
        access_token: accessToken
      }
    };

    const eighthResponse = await axios(eighthRequestConfig);
    await saveRequestToFile('kedelapan.txt', eighthRequestConfig); // Save the eighth request configuration
    console.log(eighthResponse.data);
    await saveRequestToFile('respons_kedelapan.txt', eighthResponse.data); // Save the eighth response

    console.log('Permintaan kedelapan selesai.');
  } catch (error) {
    console.error('Error saat melakukan permintaan kedelapan:', error);
  }
};

rl.question('Masukkan nomor telepon Anda: ', async (phoneNumber) => {
  userPhoneNumber = phoneNumber.trim();
  console.log(`Nomor telepon yang dimasukkan: ${userPhoneNumber}`);
  await performRequests();
});
