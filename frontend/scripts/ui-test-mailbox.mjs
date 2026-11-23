import { SMTPServer } from 'smtp-server';
import { writeFile, mkdtemp } from 'node:fs/promises';
import path from 'node:path';

// Only the isolated browser test server uses this mailbox. It never relays mail.
export async function startTestMailbox(scratch) {
  const directory = await mkdtemp(path.join(scratch, 'mail-'));
  const counts = new Map();
  const smtp = new SMTPServer({
    disabledCommands: ['AUTH', 'STARTTLS'],
    authOptional: true,
    logger: false,
    onRcptTo(address, _session, done) {
      const recipient = address.address;
      if (!/^caps26-(desktop|mobile)@example\.test$/.test(recipient)) return done(new Error('Outside test inbox'));
      const count = (counts.get(recipient) || 0) + 1;
      counts.set(recipient, count);
      // Simulate a rejection between the first delivery and a successful retry.
      if (count === 2) return done(new Error('Test inbox temporarily unavailable'));
      done();
    },
    onData(stream, session, done) {
      const recipient = session.envelope.rcptTo[0].address;
      const file = path.join(directory, recipient + '-' + counts.get(recipient) + '.eml');
      writeFile(file, stream, { flag: 'wx' }).then(() => done(), done);
    },
  });
  await new Promise((resolve, reject) => {
    smtp.once('error', reject);
    smtp.listen(0, '127.0.0.1', resolve);
  });
  return { smtp, directory, port: smtp.server.address().port };
}
